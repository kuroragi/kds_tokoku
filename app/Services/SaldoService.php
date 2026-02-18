<?php

namespace App\Services;

use App\Models\SaldoProvider;
use App\Models\SaldoTopup;
use App\Models\SaldoTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class SaldoService
{
    /**
     * Record a top-up and increase provider balance.
     */
    public function createTopup(array $data): SaldoTopup
    {
        try {
            return DB::transaction(function () use ($data) {
                $topup = SaldoTopup::create($data);

                $provider = SaldoProvider::findOrFail($data['saldo_provider_id']);
                $netAmount = $topup->amount - $topup->fee;
                $provider->increment('current_balance', $netAmount);

                return $topup;
            });
        } catch (Throwable $e) {
            throw ValidationException::withMessages([
                'amount' => 'Gagal menyimpan top up: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Delete a top-up and reverse balance.
     */
    public function deleteTopup(SaldoTopup $topup): void
    {
        try {
            DB::transaction(function () use ($topup) {
                $provider = $topup->saldoProvider;
                $netAmount = $topup->amount - $topup->fee;
                $provider->decrement('current_balance', $netAmount);

                $topup->delete();
            });
        } catch (Throwable $e) {
            throw ValidationException::withMessages([
                'topup' => 'Gagal menghapus top up: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Record a sale transaction and deduct provider balance.
     */
    public function createTransaction(array $data): SaldoTransaction
    {
        try {
            return DB::transaction(function () use ($data) {
                // Calculate profit
                $data['profit'] = ($data['sell_price'] ?? 0) - ($data['buy_price'] ?? 0);

                $transaction = SaldoTransaction::create($data);

                // Deduct provider balance by cost (buy_price)
                $provider = SaldoProvider::findOrFail($data['saldo_provider_id']);
                $provider->decrement('current_balance', $data['buy_price']);

                return $transaction;
            });
        } catch (Throwable $e) {
            throw ValidationException::withMessages([
                'sell_price' => 'Gagal menyimpan transaksi: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Delete a transaction and reverse balance deduction.
     */
    public function deleteTransaction(SaldoTransaction $transaction): void
    {
        try {
            DB::transaction(function () use ($transaction) {
                $provider = $transaction->saldoProvider;
                $provider->increment('current_balance', $transaction->buy_price);

                $transaction->delete();
            });
        } catch (Throwable $e) {
            throw ValidationException::withMessages([
                'transaction' => 'Gagal menghapus transaksi: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Get balance summary per provider for a business unit.
     */
    public function getBalanceSummary(?int $businessUnitId = null): array
    {
        $query = SaldoProvider::query()->active();

        if ($businessUnitId) {
            $query->byBusinessUnit($businessUnitId);
        }

        $providers = $query->withCount(['topups', 'transactions'])->get();

        $totalBalance = $providers->sum('current_balance');
        $totalTopups = SaldoTopup::query()
            ->when($businessUnitId, fn($q) => $q->where('business_unit_id', $businessUnitId))
            ->sum('amount');
        $totalTransactions = SaldoTransaction::query()
            ->when($businessUnitId, fn($q) => $q->where('business_unit_id', $businessUnitId))
            ->sum('sell_price');
        $totalProfit = SaldoTransaction::query()
            ->when($businessUnitId, fn($q) => $q->where('business_unit_id', $businessUnitId))
            ->sum('profit');

        return [
            'providers' => $providers,
            'total_balance' => $totalBalance,
            'total_topups' => $totalTopups,
            'total_transactions' => $totalTransactions,
            'total_profit' => $totalProfit,
        ];
    }
}
