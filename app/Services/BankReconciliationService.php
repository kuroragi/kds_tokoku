<?php

namespace App\Services;

use App\Imports\BankMutationImport;
use App\Models\BankAccount;
use App\Models\BankMutation;
use App\Models\BankReconciliation;
use App\Models\BankReconciliationItem;
use App\Models\FundTransfer;
use App\Models\JournalMaster;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class BankReconciliationService
{
    // ====================================================================
    // IMPORT MUTATIONS
    // ====================================================================

    /**
     * Column mapping presets for common Indonesian banks.
     */
    public static function getColumnPresets(): array
    {
        return [
            'bca' => [
                'label' => 'BCA',
                'date' => 'tanggal',
                'description' => 'keterangan',
                'debit' => 'mutasi_debet',      // uang masuk
                'credit' => 'mutasi_kredit',     // uang keluar
                'balance' => 'saldo',
                'reference' => null,
                'date_format' => 'd/m/Y',
            ],
            'bni' => [
                'label' => 'BNI',
                'date' => 'tanggal',
                'description' => 'keterangan',
                'debit' => 'debet',
                'credit' => 'kredit',
                'balance' => 'saldo',
                'reference' => 'no_ref',
                'date_format' => 'd/m/Y',
            ],
            'bri' => [
                'label' => 'BRI',
                'date' => 'tanggal',
                'description' => 'uraian',
                'debit' => 'debet',
                'credit' => 'kredit',
                'balance' => 'saldo',
                'reference' => 'referensi',
                'date_format' => 'd/m/Y',
            ],
            'mandiri' => [
                'label' => 'Mandiri',
                'date' => 'tanggal',
                'description' => 'keterangan',
                'debit' => 'debit',
                'credit' => 'credit',
                'balance' => 'saldo',
                'reference' => 'no_referensi',
                'date_format' => 'd/m/Y',
            ],
            'custom' => [
                'label' => 'Custom (Manual Mapping)',
                'date' => '',
                'description' => '',
                'debit' => '',
                'credit' => '',
                'balance' => '',
                'reference' => '',
                'date_format' => 'Y-m-d',
            ],
        ];
    }

    /**
     * Import bank mutations from uploaded file.
     *
     * @param string $filePath   Uploaded file path
     * @param int    $bankAccountId
     * @param int    $businessUnitId
     * @param array  $columnMapping ['date' => 'col_name', 'description' => 'col_name', ...]
     * @param string $dateFormat    PHP date format
     * @return array ['imported' => count, 'skipped' => count, 'batch' => string]
     */
    public function importMutations(
        string $filePath,
        int $bankAccountId,
        int $businessUnitId,
        array $columnMapping,
        string $dateFormat = 'd/m/Y'
    ): array {
        $import = new BankMutationImport();
        Excel::import($import, $filePath);

        $rows = $import->getRows();
        if (empty($rows)) {
            throw ValidationException::withMessages([
                'file' => 'File tidak memiliki data atau format kolom tidak sesuai.',
            ]);
        }

        $batch = 'IMP-' . date('Ymd-His') . '-' . Str::random(4);
        $imported = 0;
        $skipped = 0;

        try {
            DB::transaction(function () use ($rows, $bankAccountId, $businessUnitId, $columnMapping, $dateFormat, $batch, &$imported, &$skipped) {
                foreach ($rows as $row) {
                    // Normalize row keys to lowercase
                    $row = array_change_key_case($row, CASE_LOWER);

                    $dateCol = strtolower($columnMapping['date'] ?? '');
                    $descCol = strtolower($columnMapping['description'] ?? '');
                    $debitCol = strtolower($columnMapping['debit'] ?? '');
                    $creditCol = strtolower($columnMapping['credit'] ?? '');
                    $balanceCol = strtolower($columnMapping['balance'] ?? '');
                    $refCol = strtolower($columnMapping['reference'] ?? '');

                    $dateRaw = $row[$dateCol] ?? null;
                    $desc = trim($row[$descCol] ?? '');

                    if (!$dateRaw || !$desc) {
                        $skipped++;
                        continue;
                    }

                    // Parse date
                    try {
                        if (is_numeric($dateRaw)) {
                            // Excel serial date number
                            $transDate = Carbon::createFromTimestamp(
                                ($dateRaw - 25569) * 86400
                            )->startOfDay();
                        } else {
                            $transDate = Carbon::createFromFormat($dateFormat, trim($dateRaw));
                        }
                    } catch (\Exception $e) {
                        $skipped++;
                        continue;
                    }

                    $debit = $this->parseAmount($row[$debitCol] ?? 0);
                    $credit = $this->parseAmount($row[$creditCol] ?? 0);
                    $balance = $this->parseAmount($row[$balanceCol] ?? 0);
                    $ref = $refCol ? trim($row[$refCol] ?? '') : null;

                    if ($debit <= 0 && $credit <= 0) {
                        $skipped++;
                        continue;
                    }

                    BankMutation::create([
                        'business_unit_id' => $businessUnitId,
                        'bank_account_id' => $bankAccountId,
                        'transaction_date' => $transDate->format('Y-m-d'),
                        'description' => $desc,
                        'reference_no' => $ref ?: null,
                        'debit' => $debit,
                        'credit' => $credit,
                        'balance' => $balance,
                        'status' => 'unmatched',
                        'import_batch' => $batch,
                        'raw_data' => json_encode($row),
                    ]);

                    $imported++;
                }
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw ValidationException::withMessages([
                'file' => 'Gagal import mutasi: ' . $e->getMessage(),
            ]);
        }

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'batch' => $batch,
        ];
    }

    /**
     * Parse amount from string — removes dots/commas as thousand separators.
     */
    private function parseAmount($value): float
    {
        if (is_numeric($value)) {
            return abs((float) $value);
        }

        $str = (string) $value;
        // Remove thousand separators (dots in Indonesian format)
        $str = str_replace('.', '', $str);
        // Replace comma with dot for decimal
        $str = str_replace(',', '.', $str);
        // Remove non-numeric except dot and minus
        $str = preg_replace('/[^0-9.\-]/', '', $str);

        return abs((float) $str);
    }

    /**
     * Delete all mutations in a batch.
     */
    public function deleteBatch(string $batch): int
    {
        return BankMutation::where('import_batch', $batch)
            ->where('status', 'unmatched')
            ->delete();
    }

    // ====================================================================
    // RECONCILIATION
    // ====================================================================

    /**
     * Create a new reconciliation session.
     */
    public function createReconciliation(array $data): BankReconciliation
    {
        $bankAccount = BankAccount::findOrFail($data['bank_account_id']);

        $recon = BankReconciliation::create([
            'business_unit_id' => $data['business_unit_id'],
            'bank_account_id' => $data['bank_account_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'bank_statement_balance' => $data['bank_statement_balance'] ?? 0,
            'system_balance' => $bankAccount->current_balance,
            'difference' => ($data['bank_statement_balance'] ?? 0) - $bankAccount->current_balance,
            'status' => 'draft',
            'notes' => $data['notes'] ?? null,
        ]);

        // Load matching mutations into reconciliation items
        $mutations = BankMutation::where('bank_account_id', $data['bank_account_id'])
            ->whereBetween('transaction_date', [$data['start_date'], $data['end_date']])
            ->orderBy('transaction_date')
            ->get();

        foreach ($mutations as $mutation) {
            BankReconciliationItem::create([
                'bank_reconciliation_id' => $recon->id,
                'bank_mutation_id' => $mutation->id,
                'match_type' => $mutation->status === 'matched' ? 'auto_matched' : 'unmatched',
                'matched_journal_id' => $mutation->matched_journal_id,
                'matched_fund_transfer_id' => $mutation->matched_fund_transfer_id,
            ]);
        }

        // Auto-match
        $this->autoMatch($recon);

        $recon->recalculateCounts();

        return $recon->fresh(['bankAccount.bank', 'items.mutation']);
    }

    /**
     * Auto-match mutations with journal entries and fund transfers.
     */
    public function autoMatch(BankReconciliation $recon): int
    {
        $matchedCount = 0;
        $items = $recon->items()->where('match_type', 'unmatched')->with('mutation')->get();

        foreach ($items as $item) {
            $mutation = $item->mutation;
            if (!$mutation) continue;

            // 1. Try match by reference_no with JournalMaster
            if ($mutation->reference_no) {
                $journal = JournalMaster::where('journal_no', $mutation->reference_no)
                    ->orWhere('reference', $mutation->reference_no)
                    ->first();

                if ($journal) {
                    $this->matchItem($item, 'auto_matched', $journal->id, null);
                    $matchedCount++;
                    continue;
                }
            }

            // 2. Try match with fund_transfers by amount + date
            $amount = $mutation->debit > 0 ? $mutation->debit : $mutation->credit;
            $transfers = FundTransfer::where('transfer_date', $mutation->transaction_date)
                ->where(function ($q) use ($amount) {
                    $q->where('amount', $amount)
                      ->orWhereRaw('amount + admin_fee = ?', [$amount]);
                })
                ->where('business_unit_id', $mutation->business_unit_id)
                ->get();

            if ($transfers->count() === 1) {
                $transfer = $transfers->first();
                // Verify bank account matches
                $matchesSource = $transfer->source_bank_account_id == $mutation->bank_account_id;
                $matchesDest = $transfer->destination_bank_account_id == $mutation->bank_account_id;

                if ($matchesSource || $matchesDest) {
                    $this->matchItem($item, 'auto_matched', null, $transfer->id);
                    $matchedCount++;
                    continue;
                }
            }

            // 3. Try match with journal entries by amount + date
            $journals = JournalMaster::where('date', $mutation->transaction_date)
                ->where('business_unit_id', $mutation->business_unit_id)
                ->whereHas('journals', function ($q) use ($amount) {
                    $q->where('debit', $amount)->orWhere('credit', $amount);
                })
                ->get();

            if ($journals->count() === 1) {
                $this->matchItem($item, 'auto_matched', $journals->first()->id, null);
                $matchedCount++;
            }
        }

        return $matchedCount;
    }

    /**
     * Manually match a reconciliation item.
     */
    public function manualMatch(
        int $reconciliationItemId,
        ?int $journalId = null,
        ?int $fundTransferId = null
    ): BankReconciliationItem {
        $item = BankReconciliationItem::findOrFail($reconciliationItemId);

        $this->matchItem($item, 'manual_matched', $journalId, $fundTransferId);

        $item->reconciliation->recalculateCounts();

        return $item->fresh();
    }

    /**
     * Unmatch a reconciliation item.
     */
    public function unmatchItem(int $reconciliationItemId): BankReconciliationItem
    {
        $item = BankReconciliationItem::findOrFail($reconciliationItemId);
        $mutation = $item->mutation;

        $item->update([
            'match_type' => 'unmatched',
            'matched_journal_id' => null,
            'matched_fund_transfer_id' => null,
        ]);

        if ($mutation) {
            $mutation->update([
                'status' => 'unmatched',
                'matched_journal_id' => null,
                'matched_fund_transfer_id' => null,
            ]);
        }

        $item->reconciliation->recalculateCounts();

        return $item->fresh();
    }

    /**
     * Ignore a reconciliation item.
     */
    public function ignoreItem(int $reconciliationItemId, ?string $notes = null): BankReconciliationItem
    {
        $item = BankReconciliationItem::findOrFail($reconciliationItemId);

        $item->update([
            'match_type' => 'ignored',
            'notes' => $notes,
        ]);

        if ($item->mutation) {
            $item->mutation->update(['status' => 'ignored']);
        }

        $item->reconciliation->recalculateCounts();

        return $item->fresh();
    }

    /**
     * Mark reconciliation as completed.
     */
    public function completeReconciliation(int $reconciliationId): BankReconciliation
    {
        $recon = BankReconciliation::findOrFail($reconciliationId);

        if ($recon->isCompleted()) {
            throw ValidationException::withMessages([
                'reconciliation' => 'Rekonsiliasi sudah selesai.',
            ]);
        }

        $recon->recalculateCounts();

        $recon->update(['status' => 'completed']);

        return $recon->fresh();
    }

    /**
     * Reopen a completed reconciliation.
     */
    public function reopenReconciliation(int $reconciliationId): BankReconciliation
    {
        $recon = BankReconciliation::findOrFail($reconciliationId);
        $recon->update(['status' => 'draft']);
        return $recon->fresh();
    }

    /**
     * Delete a draft reconciliation.
     */
    public function deleteReconciliation(int $reconciliationId): void
    {
        $recon = BankReconciliation::findOrFail($reconciliationId);

        if ($recon->isCompleted()) {
            throw ValidationException::withMessages([
                'reconciliation' => 'Rekonsiliasi yang sudah selesai tidak dapat dihapus. Buka kembali terlebih dahulu.',
            ]);
        }

        // Unmark mutations
        foreach ($recon->items as $item) {
            if ($item->mutation && $item->isMatched()) {
                $item->mutation->update([
                    'status' => 'unmatched',
                    'matched_journal_id' => null,
                    'matched_fund_transfer_id' => null,
                ]);
            }
        }

        $recon->items()->delete();
        $recon->delete();
    }

    /**
     * Get summary statistics for a bank account.
     */
    public function getMutationSummary(int $bankAccountId, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = BankMutation::where('bank_account_id', $bankAccountId);

        if ($startDate && $endDate) {
            $query->whereBetween('transaction_date', [$startDate, $endDate]);
        }

        $total = (clone $query)->count();
        $matched = (clone $query)->where('status', 'matched')->count();
        $unmatched = (clone $query)->where('status', 'unmatched')->count();
        $ignored = (clone $query)->where('status', 'ignored')->count();
        $totalDebit = (clone $query)->sum('debit');
        $totalCredit = (clone $query)->sum('credit');

        return [
            'total' => $total,
            'matched' => $matched,
            'unmatched' => $unmatched,
            'ignored' => $ignored,
            'total_debit' => (float) $totalDebit,
            'total_credit' => (float) $totalCredit,
        ];
    }

    // ==================== PRIVATE HELPERS ====================

    private function matchItem(
        BankReconciliationItem $item,
        string $matchType,
        ?int $journalId,
        ?int $fundTransferId
    ): void {
        $item->update([
            'match_type' => $matchType,
            'matched_journal_id' => $journalId,
            'matched_fund_transfer_id' => $fundTransferId,
        ]);

        if ($item->mutation) {
            $item->mutation->update([
                'status' => 'matched',
                'matched_journal_id' => $journalId,
                'matched_fund_transfer_id' => $fundTransferId,
            ]);
        }
    }
}
