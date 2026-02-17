<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetDepreciation;
use App\Models\AssetDisposal;
use App\Models\BusinessUnit;
use App\Models\JournalMaster;
use App\Models\Period;
use Illuminate\Support\Facades\DB;

class AssetService
{
    protected JournalService $journalService;

    public function __construct(JournalService $journalService)
    {
        $this->journalService = $journalService;
    }

    /**
     * Hitung penyusutan bulanan untuk satu aset
     */
    public function calculateMonthlyDepreciation(Asset $asset): int
    {
        $bookValue = $this->getCurrentBookValue($asset);

        if ($bookValue <= $asset->salvage_value) {
            return 0;
        }

        if ($asset->depreciation_method === 'straight_line') {
            $monthly = (int) round(($asset->acquisition_cost - $asset->salvage_value) / $asset->useful_life_months);
            // Jangan melewati salvage value
            if ($bookValue - $monthly < $asset->salvage_value) {
                $monthly = $bookValue - $asset->salvage_value;
            }
            return max(0, $monthly);
        }

        // Declining balance: tarif = 2 / (masa manfaat dalam tahun)
        $usefulLifeYears = $asset->useful_life_months / 12;
        $annualRate = 2 / max(1, $usefulLifeYears);
        $monthly = (int) round($bookValue * $annualRate / 12);

        if ($bookValue - $monthly < $asset->salvage_value) {
            $monthly = $bookValue - $asset->salvage_value;
        }

        return max(0, $monthly);
    }

    /**
     * Nilai buku saat ini
     */
    public function getCurrentBookValue(Asset $asset): int
    {
        $totalDepreciation = (int) $asset->depreciations()->sum('depreciation_amount');
        return max(0, $asset->acquisition_cost - $totalDepreciation);
    }

    /**
     * Total akumulasi penyusutan
     */
    public function getAccumulatedDepreciation(Asset $asset): int
    {
        return (int) $asset->depreciations()->sum('depreciation_amount');
    }

    /**
     * Preview penyusutan untuk satu periode (tanpa menyimpan)
     */
    public function previewDepreciation(int $businessUnitId, int $periodId): array
    {
        $period = Period::findOrFail($periodId);
        $assets = Asset::where('business_unit_id', $businessUnitId)
            ->where('status', 'active')
            ->with('assetCategory')
            ->get();

        $preview = [];
        foreach ($assets as $asset) {
            // Skip jika sudah ada penyusutan di periode ini
            if ($asset->depreciations()->where('period_id', $periodId)->exists()) {
                continue;
            }
            // Skip jika tanggal perolehan setelah akhir periode
            if ($asset->acquisition_date > $period->end_date) {
                continue;
            }

            $bookValue = $this->getCurrentBookValue($asset);
            if ($bookValue <= $asset->salvage_value) {
                continue;
            }

            $amount = $this->calculateMonthlyDepreciation($asset);
            if ($amount <= 0) continue;

            $accumulated = $this->getAccumulatedDepreciation($asset) + $amount;

            $preview[] = [
                'asset' => $asset,
                'depreciation_amount' => $amount,
                'accumulated_depreciation' => $accumulated,
                'book_value' => $asset->acquisition_cost - $accumulated,
            ];
        }

        return $preview;
    }

    /**
     * Proses penyusutan batch untuk satu periode
     */
    public function processDepreciation(int $businessUnitId, int $periodId): array
    {
        $period = Period::findOrFail($periodId);
        $preview = $this->previewDepreciation($businessUnitId, $periodId);

        if (empty($preview)) {
            return [];
        }

        DB::beginTransaction();
        try {
            $results = [];
            $totalDepreciation = 0;

            foreach ($preview as $item) {
                $depreciation = AssetDepreciation::create([
                    'asset_id' => $item['asset']->id,
                    'period_id' => $periodId,
                    'depreciation_date' => $period->end_date,
                    'depreciation_amount' => $item['depreciation_amount'],
                    'accumulated_depreciation' => $item['accumulated_depreciation'],
                    'book_value' => $item['book_value'],
                ]);

                $results[] = $depreciation;
                $totalDepreciation += $item['depreciation_amount'];
            }

            // Buat jurnal penyesuaian gabungan
            $journalMaster = null;
            if ($totalDepreciation > 0) {
                $journalMaster = $this->createDepreciationJournal($businessUnitId, $period, $totalDepreciation);

                if ($journalMaster) {
                    foreach ($results as $dep) {
                        $dep->update(['journal_master_id' => $journalMaster->id]);
                    }
                }
            }

            DB::commit();
            return $results;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Buat jurnal penyesuaian penyusutan
     */
    protected function createDepreciationJournal(int $businessUnitId, Period $period, int $totalAmount): ?JournalMaster
    {
        $businessUnit = BusinessUnit::find($businessUnitId);
        $coaExpense = $businessUnit->getCoaByKey('beban_penyusutan');
        $coaAccumulated = $businessUnit->getCoaByKey('akumulasi_penyusutan');

        if (!$coaExpense || !$coaAccumulated) {
            return null;
        }

        return $this->journalService->createJournalEntry([
            'type' => 'adjustment',
            'journal_date' => $period->end_date->format('Y-m-d'),
            'reference' => 'DEP/' . $period->year . '/' . str_pad($period->month, 2, '0', STR_PAD_LEFT),
            'description' => 'Penyusutan aset bulan ' . $period->period_name,
            'id_period' => $period->id,
            'entries' => [
                [
                    'coa_code' => $coaExpense->code,
                    'description' => 'Beban penyusutan aset',
                    'debit' => $totalAmount,
                    'credit' => 0,
                ],
                [
                    'coa_code' => $coaAccumulated->code,
                    'description' => 'Akumulasi penyusutan aset',
                    'debit' => 0,
                    'credit' => $totalAmount,
                ],
            ],
        ]);
    }

    /**
     * Buat jurnal pengadaan aset
     */
    public function createAcquisitionJournal(Asset $asset, string $paymentCoaKey = 'kas_utama'): ?JournalMaster
    {
        $businessUnit = $asset->businessUnit;
        $coaAsset = $businessUnit->getCoaByKey('peralatan');
        $coaPayment = $businessUnit->getCoaByKey($paymentCoaKey);

        if (!$coaAsset || !$coaPayment) {
            return null;
        }

        $period = Period::where('start_date', '<=', $asset->acquisition_date)
            ->where('end_date', '>=', $asset->acquisition_date)
            ->first();

        if (!$period) {
            return null;
        }

        $journal = $this->journalService->createJournalEntry([
            'type' => 'general',
            'journal_date' => $asset->acquisition_date->format('Y-m-d'),
            'reference' => 'ACQ/' . $asset->code,
            'description' => 'Pengadaan aset: ' . $asset->name,
            'id_period' => $period->id,
            'entries' => [
                [
                    'coa_code' => $coaAsset->code,
                    'description' => 'Pengadaan ' . $asset->name,
                    'debit' => $asset->acquisition_cost,
                    'credit' => 0,
                ],
                [
                    'coa_code' => $coaPayment->code,
                    'description' => 'Pembayaran aset ' . $asset->name,
                    'debit' => 0,
                    'credit' => $asset->acquisition_cost,
                ],
            ],
        ]);

        $asset->update(['journal_master_id' => $journal->id]);

        return $journal;
    }

    /**
     * Buat jurnal disposal aset
     */
    public function createDisposalJournal(AssetDisposal $disposal): ?JournalMaster
    {
        $asset = $disposal->asset;
        $businessUnit = $asset->businessUnit;

        $coaAsset = $businessUnit->getCoaByKey('peralatan');
        $coaAccumulated = $businessUnit->getCoaByKey('akumulasi_penyusutan');
        $coaCash = $businessUnit->getCoaByKey('kas_utama');

        if (!$coaAsset || !$coaAccumulated) {
            return null;
        }

        $period = Period::where('start_date', '<=', $disposal->disposal_date)
            ->where('end_date', '>=', $disposal->disposal_date)
            ->first();

        if (!$period) {
            return null;
        }

        $accumulated = $this->getAccumulatedDepreciation($asset);
        $entries = [];

        // Debit: Akumulasi Penyusutan (hapus)
        if ($accumulated > 0) {
            $entries[] = [
                'coa_code' => $coaAccumulated->code,
                'description' => 'Hapus akumulasi penyusutan ' . $asset->name,
                'debit' => $accumulated,
                'credit' => 0,
            ];
        }

        // Debit: Kas (jika dijual)
        if ($disposal->disposal_amount > 0 && $coaCash) {
            $entries[] = [
                'coa_code' => $coaCash->code,
                'description' => 'Penerimaan disposal ' . $asset->name,
                'debit' => $disposal->disposal_amount,
                'credit' => 0,
            ];
        }

        // Credit: Aset (hapus)
        $entries[] = [
            'coa_code' => $coaAsset->code,
            'description' => 'Pelepasan aset ' . $asset->name,
            'debit' => 0,
            'credit' => $asset->acquisition_cost,
        ];

        // Gain/Loss
        $gainLoss = $disposal->gain_loss;
        if ($gainLoss > 0) {
            $coaGain = $businessUnit->getCoaByKey('pendapatan_lain');
            if ($coaGain) {
                $entries[] = [
                    'coa_code' => $coaGain->code,
                    'description' => 'Laba disposal ' . $asset->name,
                    'debit' => 0,
                    'credit' => $gainLoss,
                ];
            }
        } elseif ($gainLoss < 0) {
            $coaLoss = $businessUnit->getCoaByKey('beban_lain');
            if ($coaLoss) {
                $entries[] = [
                    'coa_code' => $coaLoss->code,
                    'description' => 'Rugi disposal ' . $asset->name,
                    'debit' => abs($gainLoss),
                    'credit' => 0,
                ];
            }
        }

        // Verifikasi balance
        $totalDebit = collect($entries)->sum('debit');
        $totalCredit = collect($entries)->sum('credit');
        if ($totalDebit != $totalCredit) {
            return null;
        }

        $journal = $this->journalService->createJournalEntry([
            'type' => 'general',
            'journal_date' => $disposal->disposal_date->format('Y-m-d'),
            'reference' => 'DSP/' . $asset->code,
            'description' => 'Disposal aset: ' . $asset->name,
            'id_period' => $period->id,
            'entries' => $entries,
        ]);

        $disposal->update(['journal_master_id' => $journal->id]);

        return $journal;
    }
}
