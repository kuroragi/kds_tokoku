<?php

namespace App\Services;

use App\Models\FiscalCorrection;
use App\Models\LossCompensation;
use App\Models\TaxCalculation;
use App\Models\JournalMaster;
use App\Models\Period;
use Illuminate\Support\Facades\DB;

class TaxService
{
    protected FinancialReportService $reportService;
    protected JournalService $journalService;

    public function __construct(FinancialReportService $reportService, JournalService $journalService)
    {
        $this->reportService = $reportService;
        $this->journalService = $journalService;
    }

    /**
     * Get commercial profit for a year (from income statement with all journal types)
     */
    public function getCommercialProfit(int $year): array
    {
        $filters = [
            'date_from' => "{$year}-01-01",
            'date_to' => "{$year}-12-31",
        ];

        return $this->reportService->getIncomeStatement($filters);
    }

    /**
     * Get fiscal corrections summary for a year
     */
    public function getFiscalCorrectionsSummary(int $year): array
    {
        $corrections = FiscalCorrection::forYear($year)->get();

        return [
            'items' => $corrections,
            'total_positive' => $corrections->where('correction_type', 'positive')->sum('amount'),
            'total_negative' => $corrections->where('correction_type', 'negative')->sum('amount'),
        ];
    }

    /**
     * Get available loss compensations for a year
     */
    public function getAvailableLossCompensations(int $year): \Illuminate\Database\Eloquent\Collection
    {
        return LossCompensation::available($year)
            ->orderBy('source_year')
            ->get();
    }

    /**
     * Calculate full tax computation for a year
     */
    public function calculateTax(int $year, float $taxRate = 22.00): array
    {
        // 1. Commercial profit
        $incomeStatement = $this->getCommercialProfit($year);
        $commercialProfit = (int) $incomeStatement['net_income'];

        // 2. Fiscal corrections
        $corrections = $this->getFiscalCorrectionsSummary($year);
        $totalPositive = (int) $corrections['total_positive'];
        $totalNegative = (int) $corrections['total_negative'];

        // 3. Fiscal profit
        $fiscalProfit = $commercialProfit + $totalPositive - $totalNegative;

        // 4. Loss compensation (only if fiscal profit > 0)
        $lossCompensationAmount = 0;
        $availableLosses = $this->getAvailableLossCompensations($year);
        if ($fiscalProfit > 0) {
            $remaining = $fiscalProfit;
            foreach ($availableLosses as $loss) {
                if ($remaining <= 0) break;
                $applicable = min($remaining, $loss->remaining_amount);
                $lossCompensationAmount += $applicable;
                $remaining -= $applicable;
            }
        }

        // 5. Taxable income (PKP)
        $taxableIncome = max(0, $fiscalProfit - $lossCompensationAmount);

        // 6. Tax amount
        $taxAmount = (int) round($taxableIncome * $taxRate / 100);

        return [
            'year' => $year,
            'income_statement' => $incomeStatement,
            'commercial_profit' => $commercialProfit,
            'commercial_detail' => [
                'total_pendapatan' => $incomeStatement['total_pendapatan'],
                'total_beban' => $incomeStatement['total_beban'],
            ],
            'fiscal_corrections' => $corrections,
            'total_positive_correction' => $totalPositive,
            'total_negative_correction' => $totalNegative,
            'fiscal_profit' => $fiscalProfit,
            'loss_compensations' => $availableLosses,
            'loss_compensation_amount' => $lossCompensationAmount,
            'taxable_income' => $taxableIncome,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'net_income' => $commercialProfit - $taxAmount,
        ];
    }

    /**
     * Save or update tax calculation for a year
     */
    public function saveTaxCalculation(int $year, float $taxRate = 22.00): TaxCalculation
    {
        $calc = $this->calculateTax($year, $taxRate);

        return TaxCalculation::updateOrCreate(
            ['year' => $year],
            [
                'commercial_profit' => $calc['commercial_profit'],
                'total_positive_correction' => $calc['total_positive_correction'],
                'total_negative_correction' => $calc['total_negative_correction'],
                'fiscal_profit' => $calc['fiscal_profit'],
                'loss_compensation_amount' => $calc['loss_compensation_amount'],
                'taxable_income' => $calc['taxable_income'],
                'tax_rate' => $calc['tax_rate'],
                'tax_amount' => $calc['tax_amount'],
                'status' => 'draft',
            ]
        );
    }

    /**
     * Generate tax journal entry
     * Dr. Beban Pajak / Cr. Utang Pajak
     */
    public function generateTaxJournal(int $year, string $expenseCoaCode, string $liabilityCoaCode): JournalMaster
    {
        $taxCalc = TaxCalculation::forYear($year)->firstOrFail();

        if ($taxCalc->hasJournal()) {
            throw new \Exception('Jurnal pajak untuk tahun ini sudah dibuat.');
        }

        if ($taxCalc->tax_amount <= 0) {
            throw new \Exception('Tidak ada pajak terutang untuk dibuat jurnalnya.');
        }

        // Find December period for that year
        $period = Period::where('year', $year)->where('month', 12)->first();
        if (!$period) {
            throw new \Exception("Periode Desember {$year} belum tersedia.");
        }

        $journal = $this->journalService->createJournalEntry([
            'journal_date' => "{$year}-12-31",
            'reference' => "PPh-Badan-{$year}",
            'description' => "Jurnal Pajak Penghasilan Badan Tahun {$year}",
            'id_period' => $period->id,
            'type' => 'tax',
            'status' => 'posted',
            'entries' => [
                ['coa_code' => $expenseCoaCode, 'description' => "Beban Pajak Penghasilan {$year}", 'debit' => $taxCalc->tax_amount, 'credit' => 0],
                ['coa_code' => $liabilityCoaCode, 'description' => "Utang Pajak Penghasilan {$year}", 'debit' => 0, 'credit' => $taxCalc->tax_amount],
            ],
        ]);

        $taxCalc->update(['id_journal_master' => $journal->id]);

        return $journal;
    }

    /**
     * Finalize tax calculation — lock it and apply loss compensations
     */
    public function finalizeTaxCalculation(int $year): TaxCalculation
    {
        $taxCalc = TaxCalculation::forYear($year)->firstOrFail();

        if ($taxCalc->isFinalized()) {
            throw new \Exception('Perhitungan pajak sudah difinalisasi.');
        }

        DB::beginTransaction();
        try {
            // Apply loss compensations
            if ($taxCalc->loss_compensation_amount > 0) {
                $remaining = $taxCalc->loss_compensation_amount;
                $losses = $this->getAvailableLossCompensations($year);
                foreach ($losses as $loss) {
                    if ($remaining <= 0) break;
                    $applied = $loss->applyCompensation($remaining);
                    $remaining -= $applied;
                }
            }

            // If fiscal loss, create new loss compensation record
            if ($taxCalc->fiscal_profit < 0) {
                LossCompensation::create([
                    'source_year' => $year,
                    'original_amount' => abs($taxCalc->fiscal_profit),
                    'used_amount' => 0,
                    'remaining_amount' => abs($taxCalc->fiscal_profit),
                    'expires_year' => $year + 5,
                ]);
            }

            $taxCalc->update([
                'status' => 'finalized',
                'finalized_at' => now(),
            ]);

            DB::commit();
            return $taxCalc->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
