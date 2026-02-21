<?php

namespace App\Services;

use App\Models\BusinessUnit;
use App\Models\COA;
use App\Models\OpeningBalance;
use App\Models\OpeningBalanceEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class OpeningBalanceService
{
    protected JournalService $journalService;

    public function __construct(?JournalService $journalService = null)
    {
        $this->journalService = $journalService ?? new JournalService();
    }

    /**
     * Create or update opening balance for a business unit in a period.
     *
     * @param array $data ['business_unit_id', 'period_id', 'balance_date', 'description']
     * @param array $entries [['coa_id', 'debit', 'credit', 'notes'], ...]
     * @return OpeningBalance
     */
    public function saveOpeningBalance(array $data, array $entries): OpeningBalance
    {
        try {
            return DB::transaction(function () use ($data, $entries) {
                // Check if opening balance already exists for this BU + period
                $openingBalance = OpeningBalance::where('business_unit_id', $data['business_unit_id'])
                    ->where('period_id', $data['period_id'])
                    ->first();

                if ($openingBalance && $openingBalance->isPosted()) {
                    throw ValidationException::withMessages([
                        'opening_balance' => 'Saldo awal untuk unit usaha dan periode ini sudah diposting dan tidak dapat diubah.',
                    ]);
                }

                // Calculate totals
                $totalDebit = 0;
                $totalCredit = 0;
                foreach ($entries as $entry) {
                    $totalDebit += (float) ($entry['debit'] ?? 0);
                    $totalCredit += (float) ($entry['credit'] ?? 0);
                }

                $balanceData = [
                    'business_unit_id' => $data['business_unit_id'],
                    'period_id' => $data['period_id'],
                    'balance_date' => $data['balance_date'],
                    'description' => $data['description'] ?? 'Saldo Awal',
                    'total_debit' => $totalDebit,
                    'total_credit' => $totalCredit,
                    'status' => 'draft',
                ];

                if ($openingBalance) {
                    // Update existing
                    $openingBalance->update($balanceData);
                    // Delete old entries
                    $openingBalance->entries()->delete();
                } else {
                    $openingBalance = OpeningBalance::create($balanceData);
                }

                // Create entries (only those with amounts > 0)
                foreach ($entries as $entry) {
                    $debit = (float) ($entry['debit'] ?? 0);
                    $credit = (float) ($entry['credit'] ?? 0);

                    if ($debit <= 0 && $credit <= 0) {
                        continue; // Skip zero entries
                    }

                    $coa = COA::find($entry['coa_id']);
                    if (!$coa) {
                        continue;
                    }

                    OpeningBalanceEntry::create([
                        'opening_balance_id' => $openingBalance->id,
                        'coa_id' => $coa->id,
                        'coa_code' => $coa->code,
                        'coa_name' => $coa->name,
                        'debit' => $debit,
                        'credit' => $credit,
                        'notes' => $entry['notes'] ?? null,
                    ]);
                }

                return $openingBalance->fresh(['entries.coa', 'businessUnit', 'period']);
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw ValidationException::withMessages([
                'opening_balance' => 'Gagal menyimpan saldo awal: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Post opening balance — creates a journal entry with type 'opening'.
     */
    public function postOpeningBalance(OpeningBalance $openingBalance): OpeningBalance
    {
        if ($openingBalance->isPosted()) {
            throw ValidationException::withMessages([
                'opening_balance' => 'Saldo awal sudah diposting.',
            ]);
        }

        if (!$openingBalance->isBalanced()) {
            throw ValidationException::withMessages([
                'opening_balance' => 'Saldo awal belum balance. Total debit harus sama dengan total credit.',
            ]);
        }

        $openingBalance->load('entries.coa', 'businessUnit', 'period');

        if ($openingBalance->entries->isEmpty()) {
            throw ValidationException::withMessages([
                'opening_balance' => 'Saldo awal tidak memiliki entri.',
            ]);
        }

        try {
            return DB::transaction(function () use ($openingBalance) {
                $journalEntries = [];

                foreach ($openingBalance->entries as $entry) {
                    if ((float) $entry->debit > 0) {
                        $journalEntries[] = [
                            'coa_code' => $entry->coa_code,
                            'description' => 'Saldo Awal - ' . $entry->coa_name,
                            'debit' => (float) $entry->debit,
                            'credit' => 0,
                        ];
                    }
                    if ((float) $entry->credit > 0) {
                        $journalEntries[] = [
                            'coa_code' => $entry->coa_code,
                            'description' => 'Saldo Awal - ' . $entry->coa_name,
                            'debit' => 0,
                            'credit' => (float) $entry->credit,
                        ];
                    }
                }

                $journal = $this->journalService->createJournalEntry([
                    'journal_date' => $openingBalance->balance_date->format('Y-m-d'),
                    'reference' => 'OB/' . $openingBalance->businessUnit->code . '/' . $openingBalance->period->code,
                    'description' => 'Saldo Awal - ' . $openingBalance->businessUnit->name . ' - ' . $openingBalance->period->name,
                    'id_period' => $openingBalance->period_id,
                    'type' => 'opening',
                    'status' => 'posted',
                    'entries' => $journalEntries,
                ]);

                $openingBalance->update([
                    'status' => 'posted',
                    'journal_master_id' => $journal->id,
                ]);

                return $openingBalance->fresh(['entries.coa', 'businessUnit', 'period', 'journalMaster']);
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw ValidationException::withMessages([
                'opening_balance' => 'Gagal posting saldo awal: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Unpost opening balance — deletes the journal entry and reverts status.
     */
    public function unpostOpeningBalance(OpeningBalance $openingBalance): OpeningBalance
    {
        if (!$openingBalance->isPosted()) {
            throw ValidationException::withMessages([
                'opening_balance' => 'Saldo awal belum diposting.',
            ]);
        }

        try {
            return DB::transaction(function () use ($openingBalance) {
                // Delete associated journal
                if ($openingBalance->journal_master_id) {
                    $journal = $openingBalance->journalMaster;
                    if ($journal) {
                        $journal->journals()->delete();
                        $journal->delete();
                    }
                }

                $openingBalance->update([
                    'status' => 'draft',
                    'journal_master_id' => null,
                ]);

                return $openingBalance->fresh(['entries.coa', 'businessUnit', 'period']);
            });
        } catch (Throwable $e) {
            throw ValidationException::withMessages([
                'opening_balance' => 'Gagal unpost saldo awal: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Delete opening balance (only if draft).
     */
    public function deleteOpeningBalance(OpeningBalance $openingBalance): bool
    {
        if ($openingBalance->isPosted()) {
            throw ValidationException::withMessages([
                'opening_balance' => 'Tidak dapat menghapus saldo awal yang sudah diposting. Unpost terlebih dahulu.',
            ]);
        }

        $openingBalance->entries()->delete();
        return $openingBalance->delete();
    }

    /**
     * Get COA accounts available for opening balance for a business unit.
     * Returns all COAs mapped to the business unit via BusinessUnitCoaMapping,
     * plus any additional leaf COAs that might be relevant.
     */
    public function getAvailableCoaAccounts(int $businessUnitId): \Illuminate\Support\Collection
    {
        $businessUnit = BusinessUnit::with('coaMappings.coa')->find($businessUnitId);

        if (!$businessUnit) {
            return collect();
        }

        // Get all mapped COA IDs
        $mappedCoaIds = $businessUnit->coaMappings->pluck('coa_id')->filter()->toArray();

        // Get all active leaf COAs, marking which are mapped
        $coas = COA::active()
            ->leafAccounts()
            ->orderBy('code')
            ->get()
            ->map(function ($coa) use ($mappedCoaIds) {
                $coa->is_mapped = in_array($coa->id, $mappedCoaIds);
                return $coa;
            });

        return $coas;
    }

    /**
     * Prepare entries data from existing opening balance for form editing.
     */
    public function getEntriesForForm(OpeningBalance $openingBalance): array
    {
        $entries = [];
        foreach ($openingBalance->entries as $entry) {
            $entries[$entry->coa_id] = [
                'coa_id' => $entry->coa_id,
                'coa_code' => $entry->coa_code,
                'coa_name' => $entry->coa_name,
                'debit' => (float) $entry->debit,
                'credit' => (float) $entry->credit,
                'notes' => $entry->notes,
            ];
        }
        return $entries;
    }
}
