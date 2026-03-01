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

    // ─── Book Value & Depreciation Helpers ──────────────────────────

    /**
     * Nilai buku saat ini (termasuk akumulasi awal untuk saldo awal).
     */
    public function getCurrentBookValue(Asset $asset): int
    {
        return max(0, $asset->acquisition_cost - $this->getAccumulatedDepreciation($asset));
    }

    /**
     * Total akumulasi penyusutan (initial + system-recorded).
     */
    public function getAccumulatedDepreciation(Asset $asset): int
    {
        return (int) $asset->initial_accumulated_depreciation
            + (int) $asset->depreciations()->sum('depreciation_amount');
    }

    /**
     * Hitung penyusutan bulanan untuk satu aset.
     * Memperhitungkan initial_accumulated_depreciation pada book value.
     */
    public function calculateMonthlyDepreciation(Asset $asset): int
    {
        $bookValue = $this->getCurrentBookValue($asset);

        if ($bookValue <= $asset->salvage_value) {
            return 0;
        }

        if ($asset->depreciation_method === 'straight_line') {
            $monthly = (int) round(
                ($asset->acquisition_cost - $asset->salvage_value) / $asset->useful_life_months
            );
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

    // ─── Depreciation Preview & Processing ──────────────────────────

    /**
     * Preview penyusutan untuk satu periode (tanpa menyimpan).
     * Skip kategori tanpa COA penyusutan (mis. tanah).
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
            if ($asset->depreciations()->where('period_id', $periodId)->exists()) continue;
            if ($asset->acquisition_date > $period->end_date) continue;

            // Skip kategori tanpa COA penyusutan (mis. tanah)
            $category = $asset->assetCategory;
            if (!$category?->coa_expense_dep_key || !$category?->coa_accumulated_dep_key) continue;

            $bookValue = $this->getCurrentBookValue($asset);
            if ($bookValue <= $asset->salvage_value) continue;

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
     * Proses penyusutan batch untuk satu periode.
     * Jurnal dikelompokkan per pasangan COA kategori aset.
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
            $groupedByCoaKeys = []; // key => ['expense_key', 'accum_key', 'total', 'category_name']

            foreach ($preview as $item) {
                $asset = $item['asset'];
                $category = $asset->assetCategory;

                $depreciation = AssetDepreciation::create([
                    'asset_id' => $asset->id,
                    'period_id' => $periodId,
                    'depreciation_date' => $period->end_date,
                    'depreciation_amount' => $item['depreciation_amount'],
                    'accumulated_depreciation' => $item['accumulated_depreciation'],
                    'book_value' => $item['book_value'],
                ]);

                $results[] = $depreciation;

                // Group by COA key pair for journal entries
                $expenseKey = $category->coa_expense_dep_key;
                $accumKey = $category->coa_accumulated_dep_key;
                $groupKey = $expenseKey . '|' . $accumKey;

                if (!isset($groupedByCoaKeys[$groupKey])) {
                    $groupedByCoaKeys[$groupKey] = [
                        'expense_key' => $expenseKey,
                        'accum_key' => $accumKey,
                        'total' => 0,
                        'category_name' => $category->name,
                    ];
                }
                $groupedByCoaKeys[$groupKey]['total'] += $item['depreciation_amount'];
            }

            // Buat jurnal penyesuaian gabungan (per-kategori COA)
            $journalMaster = $this->createDepreciationJournal($businessUnitId, $period, $groupedByCoaKeys);

            if ($journalMaster) {
                foreach ($results as $dep) {
                    $dep->update(['journal_master_id' => $journalMaster->id]);
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
     * Buat jurnal penyesuaian penyusutan.
     * Entries dikelompokkan per pasangan COA (beban_peny_X / akum_peny_X).
     */
    protected function createDepreciationJournal(int $businessUnitId, Period $period, array $groupedByCoaKeys): ?JournalMaster
    {
        $businessUnit = BusinessUnit::findOrFail($businessUnitId);
        $entries = [];

        foreach ($groupedByCoaKeys as $group) {
            if ($group['total'] <= 0) continue;

            $coaExpense = $businessUnit->getCoaByKey($group['expense_key']);
            $coaAccum = $businessUnit->getCoaByKey($group['accum_key']);

            if (!$coaExpense || !$coaAccum) {
                throw new \RuntimeException(
                    "COA mapping tidak ditemukan: '{$group['expense_key']}' atau '{$group['accum_key']}'. " .
                    "Pastikan mapping COA sudah diatur untuk unit usaha '{$businessUnit->name}'."
                );
            }

            $entries[] = [
                'coa_code' => $coaExpense->code,
                'description' => "Beban penyusutan {$group['category_name']}",
                'debit' => $group['total'],
                'credit' => 0,
            ];
            $entries[] = [
                'coa_code' => $coaAccum->code,
                'description' => "Akumulasi penyusutan {$group['category_name']}",
                'debit' => 0,
                'credit' => $group['total'],
            ];
        }

        if (empty($entries)) {
            return null;
        }

        return $this->journalService->createJournalEntry([
            'business_unit_id' => $businessUnitId,
            'type' => 'adjustment',
            'journal_date' => $period->end_date->format('Y-m-d'),
            'reference' => 'DEP/' . $period->year . '/' . str_pad($period->month, 2, '0', STR_PAD_LEFT),
            'description' => 'Penyusutan aset bulan ' . $period->period_name,
            'id_period' => $period->id,
            'entries' => $entries,
        ]);
    }

    // ─── Acquisition Journal ────────────────────────────────────────

    /**
     * Buat jurnal pengadaan aset.
     * Mendukung 3 tipe: opening_balance, purchase_cash, purchase_credit.
     * Menggunakan COA per-kategori aset.
     *
     * @throws \RuntimeException jika COA mapping / periode tidak ditemukan
     */
    public function createAcquisitionJournal(Asset $asset, ?string $paymentCoaKey = null): JournalMaster
    {
        $businessUnit = $asset->businessUnit;
        $category = $asset->assetCategory;

        // Resolve COA aset dari kategori
        $coaAssetKey = $category->coa_asset_key;
        if (!$coaAssetKey) {
            throw new \RuntimeException(
                "Kategori aset '{$category->name}' belum memiliki mapping COA aset. " .
                "Atur terlebih dahulu di pengaturan kategori."
            );
        }

        $coaAsset = $businessUnit->getCoaByKey($coaAssetKey);
        if (!$coaAsset) {
            throw new \RuntimeException(
                "COA mapping '{$coaAssetKey}' tidak ditemukan untuk unit usaha '{$businessUnit->name}'."
            );
        }

        // Resolve periode
        $period = Period::where('start_date', '<=', $asset->acquisition_date)
            ->where('end_date', '>=', $asset->acquisition_date)
            ->first();

        if (!$period) {
            throw new \RuntimeException(
                "Periode akuntansi belum tersedia untuk tanggal " . $asset->acquisition_date->format('d/m/Y') . "."
            );
        }

        // Build entries berdasarkan tipe perolehan
        $entries = match ($asset->acquisition_type) {
            'opening_balance' => $this->buildOpeningBalanceEntries($asset, $businessUnit, $coaAsset),
            'purchase_cash'   => $this->buildPurchaseCashEntries($asset, $businessUnit, $coaAsset, $paymentCoaKey ?? 'kas_utama'),
            'purchase_credit' => $this->buildPurchaseCreditEntries($asset, $businessUnit, $coaAsset),
            default => throw new \RuntimeException("Tipe perolehan '{$asset->acquisition_type}' tidak dikenali."),
        };

        $journal = $this->journalService->createJournalEntry([
            'business_unit_id' => $businessUnit->id,
            'type' => 'general',
            'journal_date' => $asset->acquisition_date->format('Y-m-d'),
            'reference' => 'ACQ/' . $asset->code,
            'description' => 'Pengadaan aset: ' . $asset->name,
            'id_period' => $period->id,
            'entries' => $entries,
        ]);

        $asset->update(['journal_master_id' => $journal->id]);

        return $journal;
    }

    /**
     * Saldo Awal: Dr Aset, Cr Akum.Penyusutan (jika ada), Cr Hutang (jika debt/mixed), Cr Modal.
     */
    protected function buildOpeningBalanceEntries(Asset $asset, BusinessUnit $bu, $coaAsset): array
    {
        $entries = [];
        $cost = $asset->acquisition_cost;
        $initialDep = (int) $asset->initial_accumulated_depreciation;
        $debt = in_array($asset->funding_source, ['debt', 'mixed']) ? (int) $asset->remaining_debt_amount : 0;

        // Dr: Aset tetap
        $entries[] = [
            'coa_code' => $coaAsset->code,
            'description' => "Saldo awal aset: {$asset->name}",
            'debit' => $cost,
            'credit' => 0,
        ];

        // Cr: Akumulasi Penyusutan (jika ada penyusutan sebelumnya)
        if ($initialDep > 0) {
            $category = $asset->assetCategory;
            $accumKey = $category->coa_accumulated_dep_key;
            if (!$accumKey) {
                throw new \RuntimeException(
                    "Kategori '{$category->name}' tidak memiliki COA akumulasi penyusutan."
                );
            }
            $coaAccum = $bu->getCoaByKey($accumKey);
            if (!$coaAccum) {
                throw new \RuntimeException("COA mapping '{$accumKey}' tidak ditemukan.");
            }
            $entries[] = [
                'coa_code' => $coaAccum->code,
                'description' => "Akum. penyusutan saldo awal {$asset->name}",
                'debit' => 0,
                'credit' => $initialDep,
            ];
        }

        // Cr: Hutang Bank (untuk funding source debt/mixed)
        if ($debt > 0) {
            $coaDebt = $bu->getCoaByKey('hutang_bank');
            if (!$coaDebt) {
                throw new \RuntimeException("COA mapping 'hutang_bank' tidak ditemukan.");
            }
            $entries[] = [
                'coa_code' => $coaDebt->code,
                'description' => "Hutang atas aset {$asset->name}",
                'debit' => 0,
                'credit' => $debt,
            ];
        }

        // Cr: Modal Pemilik (sisa setelah akum. penyusutan dan hutang)
        $modalAmount = $cost - $initialDep - $debt;
        if ($modalAmount > 0) {
            $coaModal = $bu->getCoaByKey('modal_pemilik');
            if (!$coaModal) {
                throw new \RuntimeException("COA mapping 'modal_pemilik' tidak ditemukan.");
            }
            $entries[] = [
                'coa_code' => $coaModal->code,
                'description' => "Modal pemilik atas aset {$asset->name}",
                'debit' => 0,
                'credit' => $modalAmount,
            ];
        } elseif ($modalAmount < 0) {
            throw new \RuntimeException(
                "Konfigurasi saldo awal tidak valid: " .
                "total hutang ({$debt}) + akumulasi penyusutan ({$initialDep}) melebihi harga perolehan ({$cost})."
            );
        }

        return $entries;
    }

    /**
     * Pembelian Tunai: Dr Aset, Cr Kas/Bank.
     */
    protected function buildPurchaseCashEntries(Asset $asset, BusinessUnit $bu, $coaAsset, string $paymentCoaKey): array
    {
        $coaPayment = $bu->getCoaByKey($paymentCoaKey);
        if (!$coaPayment) {
            throw new \RuntimeException("COA mapping '{$paymentCoaKey}' tidak ditemukan.");
        }

        return [
            [
                'coa_code' => $coaAsset->code,
                'description' => "Pembelian aset {$asset->name}",
                'debit' => $asset->acquisition_cost,
                'credit' => 0,
            ],
            [
                'coa_code' => $coaPayment->code,
                'description' => "Pembayaran aset {$asset->name}",
                'debit' => 0,
                'credit' => $asset->acquisition_cost,
            ],
        ];
    }

    /**
     * Pembelian Kredit: Dr Aset, Cr Hutang Usaha.
     */
    protected function buildPurchaseCreditEntries(Asset $asset, BusinessUnit $bu, $coaAsset): array
    {
        $coaHutang = $bu->getCoaByKey('hutang_usaha');
        if (!$coaHutang) {
            throw new \RuntimeException("COA mapping 'hutang_usaha' tidak ditemukan.");
        }

        return [
            [
                'coa_code' => $coaAsset->code,
                'description' => "Pembelian kredit aset {$asset->name}",
                'debit' => $asset->acquisition_cost,
                'credit' => 0,
            ],
            [
                'coa_code' => $coaHutang->code,
                'description' => "Hutang usaha atas aset {$asset->name}",
                'debit' => 0,
                'credit' => $asset->acquisition_cost,
            ],
        ];
    }

    // ─── Disposal Journal ───────────────────────────────────────────

    /**
     * Buat jurnal disposal aset.
     * Menggunakan COA per-kategori aset, gain/loss ke akun khusus aset.
     *
     * @throws \RuntimeException jika COA mapping / periode tidak ditemukan
     */
    public function createDisposalJournal(AssetDisposal $disposal): JournalMaster
    {
        $asset = $disposal->asset;
        $businessUnit = $asset->businessUnit;
        $category = $asset->assetCategory;

        // Resolve category-specific COA
        $coaAssetKey = $category->coa_asset_key;
        $coaAsset = $businessUnit->getCoaByKey($coaAssetKey);
        if (!$coaAsset) {
            throw new \RuntimeException("COA mapping '{$coaAssetKey}' tidak ditemukan.");
        }

        $period = Period::where('start_date', '<=', $disposal->disposal_date)
            ->where('end_date', '>=', $disposal->disposal_date)
            ->first();

        if (!$period) {
            throw new \RuntimeException(
                "Periode akuntansi belum tersedia untuk tanggal " . $disposal->disposal_date->format('d/m/Y') . "."
            );
        }

        $accumulated = $this->getAccumulatedDepreciation($asset);
        $entries = [];

        // Dr: Akumulasi Penyusutan (hapus)
        if ($accumulated > 0) {
            $accumKey = $category->coa_accumulated_dep_key;
            $coaAccum = $businessUnit->getCoaByKey($accumKey);
            if (!$coaAccum) {
                throw new \RuntimeException("COA mapping '{$accumKey}' tidak ditemukan.");
            }
            $entries[] = [
                'coa_code' => $coaAccum->code,
                'description' => "Hapus akumulasi penyusutan {$asset->name}",
                'debit' => $accumulated,
                'credit' => 0,
            ];
        }

        // Dr: Kas (jika dijual)
        if ($disposal->disposal_amount > 0) {
            $coaCash = $businessUnit->getCoaByKey('kas_utama');
            if (!$coaCash) {
                throw new \RuntimeException("COA mapping 'kas_utama' tidak ditemukan.");
            }
            $entries[] = [
                'coa_code' => $coaCash->code,
                'description' => "Penerimaan disposal {$asset->name}",
                'debit' => $disposal->disposal_amount,
                'credit' => 0,
            ];
        }

        // Cr: Aset (hapus nilai perolehan)
        $entries[] = [
            'coa_code' => $coaAsset->code,
            'description' => "Pelepasan aset {$asset->name}",
            'debit' => 0,
            'credit' => $asset->acquisition_cost,
        ];

        // Gain/Loss
        $gainLoss = $disposal->gain_loss;
        if ($gainLoss > 0) {
            $coaGain = $businessUnit->getCoaByKey('laba_penjualan_aset');
            if (!$coaGain) {
                throw new \RuntimeException("COA mapping 'laba_penjualan_aset' tidak ditemukan.");
            }
            $entries[] = [
                'coa_code' => $coaGain->code,
                'description' => "Laba disposal {$asset->name}",
                'debit' => 0,
                'credit' => $gainLoss,
            ];
        } elseif ($gainLoss < 0) {
            $coaLoss = $businessUnit->getCoaByKey('rugi_penjualan_aset');
            if (!$coaLoss) {
                throw new \RuntimeException("COA mapping 'rugi_penjualan_aset' tidak ditemukan.");
            }
            $entries[] = [
                'coa_code' => $coaLoss->code,
                'description' => "Rugi disposal {$asset->name}",
                'debit' => abs($gainLoss),
                'credit' => 0,
            ];
        }

        // Verifikasi balance
        $totalDebit = collect($entries)->sum('debit');
        $totalCredit = collect($entries)->sum('credit');
        if ($totalDebit !== $totalCredit) {
            throw new \RuntimeException(
                "Jurnal disposal tidak seimbang. Debit: {$totalDebit}, Kredit: {$totalCredit}."
            );
        }

        $journal = $this->journalService->createJournalEntry([
            'business_unit_id' => $businessUnit->id,
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
