<?php

namespace App\Services;

use App\Models\Period;
use App\Models\SaldoOpname;
use App\Models\SaldoOpnameDetail;
use App\Models\SaldoProvider;
use App\Models\Stock;
use App\Models\StockOpname;
use App\Models\StockOpnameDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class StockOpnameService
{
    protected JournalService $journalService;

    public function __construct(?JournalService $journalService = null)
    {
        $this->journalService = $journalService ?? new JournalService();
    }

    // ==================== NUMBER GENERATION ====================

    public function generateStockOpnameNumber(): string
    {
        $prefix = 'SOP';
        $year = date('Y');
        $month = date('m');

        $last = StockOpname::where('opname_number', 'like', "{$prefix}/{$year}/{$month}/%")
            ->orderBy('opname_number', 'desc')
            ->first();

        $number = $last ? ((int) substr($last->opname_number, -4)) + 1 : 1;

        return "{$prefix}/{$year}/{$month}/" . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    public function generateSaldoOpnameNumber(): string
    {
        $prefix = 'BOP';
        $year = date('Y');
        $month = date('m');

        $last = SaldoOpname::where('opname_number', 'like', "{$prefix}/{$year}/{$month}/%")
            ->orderBy('opname_number', 'desc')
            ->first();

        $number = $last ? ((int) substr($last->opname_number, -4)) + 1 : 1;

        return "{$prefix}/{$year}/{$month}/" . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    // ==================== STOCK OPNAME ====================

    /**
     * Create a Stock Opname with details.
     */
    public function createStockOpname(array $data, array $details): StockOpname
    {
        try {
            return DB::transaction(function () use ($data, $details) {
                $data['opname_number'] = $data['opname_number'] ?? $this->generateStockOpnameNumber();
                $data['status'] = 'draft';

                $opname = StockOpname::create($data);

                foreach ($details as $detail) {
                    $stock = Stock::findOrFail($detail['stock_id']);
                    $systemQty = (float) $stock->current_stock;
                    $actualQty = (float) $detail['actual_qty'];

                    StockOpnameDetail::create([
                        'stock_opname_id' => $opname->id,
                        'stock_id' => $detail['stock_id'],
                        'system_qty' => $systemQty,
                        'actual_qty' => $actualQty,
                        'difference' => $actualQty - $systemQty,
                        'notes' => $detail['notes'] ?? null,
                    ]);
                }

                return $opname->fresh(['details.stock', 'businessUnit']);
            });
        } catch (Throwable $e) {
            throw ValidationException::withMessages([
                'stock_opname' => 'Gagal membuat stock opname: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Approve a Stock Opname: update stock quantities and create adjustment journal.
     */
    public function approveStockOpname(StockOpname $opname): StockOpname
    {
        if ($opname->status !== 'draft') {
            throw ValidationException::withMessages([
                'status' => 'Hanya opname berstatus draft yang bisa disetujui.',
            ]);
        }

        try {
            return DB::transaction(function () use ($opname) {
                // Update stock quantities
                foreach ($opname->details as $detail) {
                    $stock = Stock::findOrFail($detail->stock_id);
                    $stock->update(['current_stock' => $detail->actual_qty]);
                }

                // Create adjustment journal if there are differences
                if ($opname->hasAdjustments()) {
                    $this->createStockOpnameJournal($opname);
                }

                $opname->update([
                    'status' => 'approved',
                    'approved_at' => now(),
                ]);

                return $opname->fresh(['details.stock', 'businessUnit']);
            });
        } catch (Throwable $e) {
            throw ValidationException::withMessages([
                'stock_opname' => 'Gagal menyetujui stock opname: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Cancel a Stock Opname.
     */
    public function cancelStockOpname(StockOpname $opname): StockOpname
    {
        if ($opname->status !== 'draft') {
            throw ValidationException::withMessages([
                'status' => 'Hanya opname berstatus draft yang bisa dibatalkan.',
            ]);
        }

        $opname->update(['status' => 'cancelled']);

        return $opname->fresh();
    }

    /**
     * Delete Stock Opname (only draft).
     */
    public function deleteStockOpname(StockOpname $opname): void
    {
        if ($opname->status !== 'draft') {
            throw ValidationException::withMessages([
                'status' => 'Hanya opname berstatus draft yang bisa dihapus.',
            ]);
        }

        DB::transaction(function () use ($opname) {
            $opname->details()->delete();
            $opname->delete();
        });
    }

    // ==================== SALDO OPNAME ====================

    /**
     * Create a Saldo Opname with details.
     */
    public function createSaldoOpname(array $data, array $details): SaldoOpname
    {
        try {
            return DB::transaction(function () use ($data, $details) {
                $data['opname_number'] = $data['opname_number'] ?? $this->generateSaldoOpnameNumber();
                $data['status'] = 'draft';

                $opname = SaldoOpname::create($data);

                foreach ($details as $detail) {
                    $provider = SaldoProvider::findOrFail($detail['saldo_provider_id']);
                    $systemBalance = (float) $provider->current_balance;
                    $actualBalance = (float) $detail['actual_balance'];

                    SaldoOpnameDetail::create([
                        'saldo_opname_id' => $opname->id,
                        'saldo_provider_id' => $detail['saldo_provider_id'],
                        'system_balance' => $systemBalance,
                        'actual_balance' => $actualBalance,
                        'difference' => $actualBalance - $systemBalance,
                        'notes' => $detail['notes'] ?? null,
                    ]);
                }

                return $opname->fresh(['details.saldoProvider', 'businessUnit']);
            });
        } catch (Throwable $e) {
            throw ValidationException::withMessages([
                'saldo_opname' => 'Gagal membuat saldo opname: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Approve a Saldo Opname: update provider balances and create adjustment journal.
     */
    public function approveSaldoOpname(SaldoOpname $opname): SaldoOpname
    {
        if ($opname->status !== 'draft') {
            throw ValidationException::withMessages([
                'status' => 'Hanya opname berstatus draft yang bisa disetujui.',
            ]);
        }

        try {
            return DB::transaction(function () use ($opname) {
                $hasDifference = false;

                foreach ($opname->details as $detail) {
                    $provider = SaldoProvider::findOrFail($detail->saldo_provider_id);
                    $provider->update(['current_balance' => $detail->actual_balance]);

                    if ($detail->difference != 0) {
                        $hasDifference = true;
                    }
                }

                if ($hasDifference) {
                    $this->createSaldoOpnameJournal($opname);
                }

                $opname->update([
                    'status' => 'approved',
                    'approved_at' => now(),
                ]);

                return $opname->fresh(['details.saldoProvider', 'businessUnit']);
            });
        } catch (Throwable $e) {
            throw ValidationException::withMessages([
                'saldo_opname' => 'Gagal menyetujui saldo opname: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Cancel Saldo Opname.
     */
    public function cancelSaldoOpname(SaldoOpname $opname): SaldoOpname
    {
        if ($opname->status !== 'draft') {
            throw ValidationException::withMessages([
                'status' => 'Hanya opname berstatus draft yang bisa dibatalkan.',
            ]);
        }

        $opname->update(['status' => 'cancelled']);

        return $opname->fresh();
    }

    /**
     * Delete Saldo Opname (only draft).
     */
    public function deleteSaldoOpname(SaldoOpname $opname): void
    {
        if ($opname->status !== 'draft') {
            throw ValidationException::withMessages([
                'status' => 'Hanya opname berstatus draft yang bisa dihapus.',
            ]);
        }

        DB::transaction(function () use ($opname) {
            $opname->details()->delete();
            $opname->delete();
        });
    }

    // ==================== JOURNAL HELPERS ====================

    /**
     * Create adjustment journal for stock opname differences.
     *
     * Surplus (actual > system):  Debit Persediaan, Credit Pendapatan Lain-lain
     * Defisit (actual < system):  Debit Beban Selisih Stok, Credit Persediaan
     */
    private function createStockOpnameJournal(StockOpname $opname): void
    {
        $period = Period::current()->open()->first();
        if (!$period) {
            return;
        }

        $totalSurplus = 0;
        $totalDeficit = 0;

        foreach ($opname->details as $detail) {
            $diff = (float) $detail->difference;
            $stock = Stock::find($detail->stock_id);
            $value = abs($diff) * (float) ($stock->buy_price ?? 0);

            if ($diff > 0) {
                $totalSurplus += $value;
            } elseif ($diff < 0) {
                $totalDeficit += $value;
            }
        }

        $entries = [];

        if ($totalSurplus > 0) {
            $entries[] = [
                'coa_code' => '1301', // Persediaan Barang
                'description' => 'Surplus Stock Opname - ' . $opname->opname_number,
                'debit' => $totalSurplus,
                'credit' => 0,
            ];
            $entries[] = [
                'coa_code' => '4201', // Pendapatan Lain-lain
                'description' => 'Surplus Stok - ' . $opname->opname_number,
                'debit' => 0,
                'credit' => $totalSurplus,
            ];
        }

        if ($totalDeficit > 0) {
            $entries[] = [
                'coa_code' => '5301', // Beban Selisih Stok
                'description' => 'Defisit Stock Opname - ' . $opname->opname_number,
                'debit' => $totalDeficit,
                'credit' => 0,
            ];
            $entries[] = [
                'coa_code' => '1301', // Persediaan Barang
                'description' => 'Defisit Stok - ' . $opname->opname_number,
                'debit' => 0,
                'credit' => $totalDeficit,
            ];
        }

        if (empty($entries)) {
            return;
        }

        // If both surplus and deficit, we need to net the persediaan entries
        // For simplicity, handle them as separate journal entries
        if ($totalSurplus > 0 && $totalDeficit > 0) {
            // Net the persediaan amounts
            $netPersediaan = $totalSurplus - $totalDeficit;
            $entries = [];

            if ($netPersediaan > 0) {
                $entries[] = [
                    'coa_code' => '1301',
                    'description' => 'Penyesuaian Persediaan - ' . $opname->opname_number,
                    'debit' => $netPersediaan,
                    'credit' => 0,
                ];
            } elseif ($netPersediaan < 0) {
                $entries[] = [
                    'coa_code' => '1301',
                    'description' => 'Penyesuaian Persediaan - ' . $opname->opname_number,
                    'debit' => 0,
                    'credit' => abs($netPersediaan),
                ];
            }

            if ($totalSurplus > 0) {
                $entries[] = [
                    'coa_code' => '4201',
                    'description' => 'Surplus Stok - ' . $opname->opname_number,
                    'debit' => 0,
                    'credit' => $totalSurplus,
                ];
            }

            if ($totalDeficit > 0) {
                $entries[] = [
                    'coa_code' => '5301',
                    'description' => 'Defisit Stok - ' . $opname->opname_number,
                    'debit' => $totalDeficit,
                    'credit' => 0,
                ];
            }
        }

        try {
            $journal = $this->journalService->createJournalEntry([
                'journal_date' => $opname->opname_date->format('Y-m-d'),
                'reference' => $opname->opname_number,
                'description' => 'Penyesuaian Stock Opname - ' . $opname->opname_number,
                'id_period' => $period->id,
                'type' => 'adjustment',
                'status' => 'posted',
                'entries' => $entries,
            ]);

            $opname->update(['journal_master_id' => $journal->id]);
        } catch (\Exception $e) {
            logger()->warning('Failed to create stock opname journal: ' . $e->getMessage());
        }
    }

    /**
     * Create adjustment journal for saldo opname differences.
     */
    private function createSaldoOpnameJournal(SaldoOpname $opname): void
    {
        $period = Period::current()->open()->first();
        if (!$period) {
            return;
        }

        $totalSurplus = 0;
        $totalDeficit = 0;

        foreach ($opname->details as $detail) {
            $diff = (float) $detail->difference;
            if ($diff > 0) {
                $totalSurplus += $diff;
            } elseif ($diff < 0) {
                $totalDeficit += abs($diff);
            }
        }

        $entries = [];

        if ($totalSurplus > 0 && $totalDeficit == 0) {
            $entries[] = [
                'coa_code' => '1101',
                'description' => 'Surplus Saldo Opname - ' . $opname->opname_number,
                'debit' => $totalSurplus,
                'credit' => 0,
            ];
            $entries[] = [
                'coa_code' => '4201',
                'description' => 'Surplus Saldo - ' . $opname->opname_number,
                'debit' => 0,
                'credit' => $totalSurplus,
            ];
        } elseif ($totalDeficit > 0 && $totalSurplus == 0) {
            $entries[] = [
                'coa_code' => '5301',
                'description' => 'Defisit Saldo Opname - ' . $opname->opname_number,
                'debit' => $totalDeficit,
                'credit' => 0,
            ];
            $entries[] = [
                'coa_code' => '1101',
                'description' => 'Defisit Saldo - ' . $opname->opname_number,
                'debit' => 0,
                'credit' => $totalDeficit,
            ];
        } else {
            // Both surplus and deficit
            $net = $totalSurplus - $totalDeficit;
            if ($net > 0) {
                $entries[] = [
                    'coa_code' => '1101',
                    'description' => 'Penyesuaian Saldo - ' . $opname->opname_number,
                    'debit' => $net,
                    'credit' => 0,
                ];
            } elseif ($net < 0) {
                $entries[] = [
                    'coa_code' => '1101',
                    'description' => 'Penyesuaian Saldo - ' . $opname->opname_number,
                    'debit' => 0,
                    'credit' => abs($net),
                ];
            }

            if ($totalSurplus > 0) {
                $entries[] = [
                    'coa_code' => '4201',
                    'description' => 'Surplus Saldo - ' . $opname->opname_number,
                    'debit' => 0,
                    'credit' => $totalSurplus,
                ];
            }

            if ($totalDeficit > 0) {
                $entries[] = [
                    'coa_code' => '5301',
                    'description' => 'Defisit Saldo - ' . $opname->opname_number,
                    'debit' => $totalDeficit,
                    'credit' => 0,
                ];
            }
        }

        if (empty($entries)) {
            return;
        }

        try {
            $journal = $this->journalService->createJournalEntry([
                'journal_date' => $opname->opname_date->format('Y-m-d'),
                'reference' => $opname->opname_number,
                'description' => 'Penyesuaian Saldo Opname - ' . $opname->opname_number,
                'id_period' => $period->id,
                'type' => 'adjustment',
                'status' => 'posted',
                'entries' => $entries,
            ]);

            $opname->update(['journal_master_id' => $journal->id]);
        } catch (\Exception $e) {
            logger()->warning('Failed to create saldo opname journal: ' . $e->getMessage());
        }
    }
}
