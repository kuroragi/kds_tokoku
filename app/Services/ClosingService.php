<?php

namespace App\Services;

use App\Models\COA;
use App\Models\Journal;
use App\Models\JournalMaster;
use App\Models\LossCompensation;
use App\Models\Period;
use Illuminate\Support\Facades\DB;

class ClosingService
{
    protected JournalService $journalService;
    protected FinancialReportService $reportService;

    public function __construct(JournalService $journalService, FinancialReportService $reportService)
    {
        $this->journalService = $journalService;
        $this->reportService = $reportService;
    }

    /**
     * Close a monthly period (lock it)
     */
    public function closeMonth(int $periodId): Period
    {
        $period = Period::findOrFail($periodId);

        if ($period->is_closed) {
            throw new \Exception("Periode '{$period->name}' sudah ditutup.");
        }

        // Check for unposted journals
        $unposted = JournalMaster::where('id_period', $periodId)
            ->where('status', 'draft')
            ->count();

        if ($unposted > 0) {
            throw new \Exception("Masih ada {$unposted} jurnal draft di periode ini. Posting atau hapus terlebih dahulu.");
        }

        $period->update([
            'is_closed' => true,
            'closed_at' => now(),
        ]);

        return $period->fresh();
    }

    /**
     * Reopen a monthly period
     */
    public function reopenMonth(int $periodId): Period
    {
        $period = Period::findOrFail($periodId);

        if (!$period->is_closed) {
            throw new \Exception("Periode '{$period->name}' belum ditutup.");
        }

        $period->update([
            'is_closed' => false,
            'closed_at' => null,
        ]);

        return $period->fresh();
    }

    /**
     * Get closing status for a year
     */
    public function getYearClosingStatus(int $year): array
    {
        $periods = Period::where('year', $year)
            ->orderBy('month')
            ->get();

        $allClosed = $periods->isNotEmpty() && $periods->every(fn ($p) => $p->is_closed);

        // Check if closing journal exists for this year
        $closingJournal = JournalMaster::closing()
            ->where('journal_no', 'like', "CLO/{$year}%")
            ->posted()
            ->first();

        return [
            'periods' => $periods,
            'all_months_closed' => $allClosed,
            'has_closing_journal' => $closingJournal !== null,
            'closing_journal' => $closingJournal,
        ];
    }

    /**
     * Perform yearly closing
     * Closes all revenue/expense accounts to Ikhtisar L/R, then to Laba Ditahan
     */
    public function closeYear(int $year, string $summaryCoaCode, string $retainedEarningsCoaCode): JournalMaster
    {
        // Validate prerequisites
        $status = $this->getYearClosingStatus($year);

        if (!$status['all_months_closed']) {
            throw new \Exception('Semua periode bulanan harus ditutup terlebih dahulu.');
        }

        if ($status['has_closing_journal']) {
            throw new \Exception("Closing tahunan {$year} sudah dilakukan.");
        }

        // Get December period
        $period = Period::where('year', $year)->where('month', 12)->first();
        if (!$period) {
            throw new \Exception("Periode Desember {$year} belum tersedia.");
        }

        // Get income statement for the year (all journal types)
        $filters = [
            'date_from' => "{$year}-01-01",
            'date_to' => "{$year}-12-31",
        ];
        $incomeStatement = $this->reportService->getIncomeStatement($filters);

        $entries = [];

        // Close Pendapatan accounts (Dr Pendapatan / Cr Ikhtisar L/R)
        foreach ($incomeStatement['pendapatan'] as $account) {
            if ($account->saldo > 0) {
                $entries[] = [
                    'coa_code' => $account->coa_code,
                    'description' => "Penutupan pendapatan {$account->coa_name}",
                    'debit' => $account->saldo,
                    'credit' => 0,
                ];
            }
        }

        // Total pendapatan ke Ikhtisar L/R
        if ($incomeStatement['total_pendapatan'] > 0) {
            $entries[] = [
                'coa_code' => $summaryCoaCode,
                'description' => 'Penutupan total pendapatan ke Ikhtisar Laba Rugi',
                'debit' => 0,
                'credit' => $incomeStatement['total_pendapatan'],
            ];
        }

        // Close Beban accounts (Dr Ikhtisar L/R / Cr Beban)
        $totalBeban = 0;
        foreach ($incomeStatement['beban'] as $account) {
            if ($account->saldo > 0) {
                $entries[] = [
                    'coa_code' => $account->coa_code,
                    'description' => "Penutupan beban {$account->coa_name}",
                    'debit' => 0,
                    'credit' => $account->saldo,
                ];
                $totalBeban += $account->saldo;
            }
        }

        if ($totalBeban > 0) {
            $entries[] = [
                'coa_code' => $summaryCoaCode,
                'description' => 'Penutupan total beban ke Ikhtisar Laba Rugi',
                'debit' => $totalBeban,
                'credit' => 0,
            ];
        }

        // Close Ikhtisar L/R to Laba Ditahan
        $netIncome = $incomeStatement['net_income'];
        if ($netIncome > 0) {
            // Laba: Dr Ikhtisar / Cr Laba Ditahan
            $entries[] = [
                'coa_code' => $summaryCoaCode,
                'description' => 'Penutupan laba bersih ke Laba Ditahan',
                'debit' => $netIncome,
                'credit' => 0,
            ];
            $entries[] = [
                'coa_code' => $retainedEarningsCoaCode,
                'description' => 'Laba bersih tahun ' . $year,
                'debit' => 0,
                'credit' => $netIncome,
            ];
        } elseif ($netIncome < 0) {
            // Rugi: Dr Laba Ditahan / Cr Ikhtisar
            $entries[] = [
                'coa_code' => $retainedEarningsCoaCode,
                'description' => 'Rugi bersih tahun ' . $year,
                'debit' => abs($netIncome),
                'credit' => 0,
            ];
            $entries[] = [
                'coa_code' => $summaryCoaCode,
                'description' => 'Penutupan rugi bersih ke Laba Ditahan',
                'debit' => 0,
                'credit' => abs($netIncome),
            ];
        }

        if (empty($entries)) {
            throw new \Exception('Tidak ada saldo akun pendapatan/beban untuk ditutup.');
        }

        return $this->journalService->createJournalEntry([
            'journal_date' => "{$year}-12-31",
            'reference' => "CLO-{$year}",
            'description' => "Jurnal Penutupan Tahun {$year}",
            'id_period' => $period->id,
            'type' => 'closing',
            'status' => 'posted',
            'entries' => $entries,
        ]);
    }
}
