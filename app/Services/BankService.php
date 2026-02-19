<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\CashAccount;
use App\Models\FundTransfer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class BankService
{
    /**
     * Create a fund transfer and update balances on both sides.
     *
     * Source is deducted by (amount + admin_fee).
     * Destination is incremented by amount.
     */
    public function createTransfer(array $data): FundTransfer
    {
        try {
            return DB::transaction(function () use ($data) {
                $transfer = FundTransfer::create($data);

                $totalDeducted = $transfer->amount + $transfer->admin_fee;

                // Deduct from source
                if ($transfer->source_type === 'cash') {
                    $cash = CashAccount::where('business_unit_id', $transfer->business_unit_id)->firstOrFail();
                    $cash->decrement('current_balance', $totalDeducted);
                } else {
                    $sourceAccount = BankAccount::findOrFail($transfer->source_bank_account_id);
                    $sourceAccount->decrement('current_balance', $totalDeducted);
                }

                // Add to destination
                if ($transfer->destination_type === 'cash') {
                    $cash = CashAccount::where('business_unit_id', $transfer->business_unit_id)->firstOrFail();
                    $cash->increment('current_balance', $transfer->amount);
                } else {
                    $destAccount = BankAccount::findOrFail($transfer->destination_bank_account_id);
                    $destAccount->increment('current_balance', $transfer->amount);
                }

                return $transfer;
            });
        } catch (Throwable $e) {
            throw ValidationException::withMessages([
                'amount' => 'Gagal menyimpan transfer: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Delete a transfer and reverse the balance changes.
     */
    public function deleteTransfer(FundTransfer $transfer): void
    {
        try {
            DB::transaction(function () use ($transfer) {
                $totalDeducted = $transfer->amount + $transfer->admin_fee;

                // Reverse source: give back
                if ($transfer->source_type === 'cash') {
                    $cash = CashAccount::where('business_unit_id', $transfer->business_unit_id)->firstOrFail();
                    $cash->increment('current_balance', $totalDeducted);
                } else {
                    $sourceAccount = BankAccount::findOrFail($transfer->source_bank_account_id);
                    $sourceAccount->increment('current_balance', $totalDeducted);
                }

                // Reverse destination: take back
                if ($transfer->destination_type === 'cash') {
                    $cash = CashAccount::where('business_unit_id', $transfer->business_unit_id)->firstOrFail();
                    $cash->decrement('current_balance', $transfer->amount);
                } else {
                    $destAccount = BankAccount::findOrFail($transfer->destination_bank_account_id);
                    $destAccount->decrement('current_balance', $transfer->amount);
                }

                $transfer->delete();
            });
        } catch (Throwable $e) {
            throw ValidationException::withMessages([
                'transfer' => 'Gagal menghapus transfer: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Get balance summary for a business unit.
     */
    public function getBalanceSummary(?int $businessUnitId = null): array
    {
        $cashQuery = CashAccount::query()->active();
        $bankQuery = BankAccount::query()->active();

        if ($businessUnitId) {
            $cashQuery->byBusinessUnit($businessUnitId);
            $bankQuery->byBusinessUnit($businessUnitId);
        }

        $cashAccounts = $cashQuery->get();
        $bankAccounts = $bankQuery->with('bank')->get();

        return [
            'cash_accounts' => $cashAccounts,
            'bank_accounts' => $bankAccounts,
            'total_cash' => $cashAccounts->sum('current_balance'),
            'total_bank' => $bankAccounts->sum('current_balance'),
            'total_all' => $cashAccounts->sum('current_balance') + $bankAccounts->sum('current_balance'),
        ];
    }
}
