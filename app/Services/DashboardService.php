<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\CashAccount;
use App\Models\Customer;
use App\Models\Journal;
use App\Models\JournalMaster;
use App\Models\Payable;
use App\Models\Purchase;
use App\Models\Receivable;
use App\Models\Sale;
use App\Models\Stock;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Get all dashboard data for the given BU and period.
     */
    public static function getData(?int $buId, string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        return [
            'summary_cards' => self::getSummaryCards($buId, $start, $end),
            'sales_chart' => self::getSalesChart($buId, $start, $end),
            'cashflow' => self::getCashFlow($buId, $start, $end),
            'top_products' => self::getTopProducts($buId, $start, $end),
            'payable_receivable' => self::getPayableReceivable($buId),
            'low_stock' => self::getLowStock($buId),
            'recent_transactions' => self::getRecentTransactions($buId, 10),
            'bank_balances' => self::getBankBalances($buId),
        ];
    }

    /**
     * Summary cards: total sales, total purchase, transaction count, customer count.
     */
    public static function getSummaryCards(?int $buId, Carbon $start, Carbon $end): array
    {
        $saleQuery = Sale::query()->whereBetween('sale_date', [$start, $end])->where('status', '!=', 'cancelled');
        $purchaseQuery = Purchase::query()->whereBetween('purchase_date', [$start, $end])->where('status', '!=', 'cancelled');
        $customerQuery = Customer::query()->whereBetween('created_at', [$start, $end]);

        if ($buId) {
            $saleQuery->where('business_unit_id', $buId);
            $purchaseQuery->where('business_unit_id', $buId);
            $customerQuery->where('business_unit_id', $buId);
        }

        // Previous period for comparison
        $periodDays = $start->diffInDays($end) + 1;
        $prevStart = $start->copy()->subDays($periodDays);
        $prevEnd = $start->copy()->subDay();

        $prevSaleQuery = Sale::query()->whereBetween('sale_date', [$prevStart, $prevEnd])->where('status', '!=', 'cancelled');
        if ($buId) $prevSaleQuery->where('business_unit_id', $buId);

        $totalSales = $saleQuery->sum('grand_total');
        $prevTotalSales = $prevSaleQuery->sum('grand_total');
        $salesGrowth = $prevTotalSales > 0 ? round(($totalSales - $prevTotalSales) / $prevTotalSales * 100, 1) : 0;

        $totalPurchase = $purchaseQuery->sum('grand_total');
        $saleCount = $saleQuery->count();
        $newCustomers = $customerQuery->count();

        return [
            'total_sales' => $totalSales,
            'sales_growth' => $salesGrowth,
            'total_purchase' => $totalPurchase,
            'sale_count' => $saleCount,
            'new_customers' => $newCustomers,
        ];
    }

    /**
     * Sales chart data grouped by day/week/month.
     */
    public static function getSalesChart(?int $buId, Carbon $start, Carbon $end): array
    {
        $diffDays = $start->diffInDays($end);

        // Group by day if <= 31 days, by week if <= 90, else by month
        if ($diffDays <= 31) {
            $groupFormat = '%Y-%m-%d';
            $labelFormat = 'd M';
        } elseif ($diffDays <= 90) {
            $groupFormat = '%x-W%v'; // ISO week
            $labelFormat = 'W';
        } else {
            $groupFormat = '%Y-%m';
            $labelFormat = 'M Y';
        }

        $query = Sale::query()
            ->selectRaw("DATE_FORMAT(sale_date, '{$groupFormat}') as period, SUM(grand_total) as total, COUNT(*) as count")
            ->whereBetween('sale_date', [$start, $end])
            ->where('status', '!=', 'cancelled')
            ->groupBy('period')
            ->orderBy('period');

        if ($buId) $query->where('business_unit_id', $buId);

        $data = $query->get();

        // Also get purchase data for overlay
        $purchaseQuery = Purchase::query()
            ->selectRaw("DATE_FORMAT(purchase_date, '{$groupFormat}') as period, SUM(grand_total) as total")
            ->whereBetween('purchase_date', [$start, $end])
            ->where('status', '!=', 'cancelled')
            ->groupBy('period')
            ->orderBy('period');

        if ($buId) $purchaseQuery->where('business_unit_id', $buId);

        $purchaseData = $purchaseQuery->pluck('total', 'period');

        $labels = [];
        $salesSeries = [];
        $purchaseSeries = [];

        foreach ($data as $row) {
            $labels[] = $row->period;
            $salesSeries[] = (float) $row->total;
            $purchaseSeries[] = (float) ($purchaseData[$row->period] ?? 0);
        }

        return [
            'labels' => $labels,
            'sales' => $salesSeries,
            'purchase' => $purchaseSeries,
        ];
    }

    /**
     * Cash flow: income vs expense from journals.
     */
    public static function getCashFlow(?int $buId, Carbon $start, Carbon $end): array
    {
        // Sum of sales (income)
        $saleQ = Sale::query()->whereBetween('sale_date', [$start, $end])->where('status', '!=', 'cancelled');
        if ($buId) $saleQ->where('business_unit_id', $buId);
        $income = (float) $saleQ->sum('grand_total');

        // Sum of purchases (expense)
        $purchaseQ = Purchase::query()->whereBetween('purchase_date', [$start, $end])->where('status', '!=', 'cancelled');
        if ($buId) $purchaseQ->where('business_unit_id', $buId);
        $expense = (float) $purchaseQ->sum('grand_total');

        return [
            'income' => $income,
            'expense' => $expense,
            'net' => $income - $expense,
        ];
    }

    /**
     * Top selling products (by sale items).
     */
    public static function getTopProducts(?int $buId, Carbon $start, Carbon $end, int $limit = 5): array
    {
        $query = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('stocks', 'sale_items.stock_id', '=', 'stocks.id')
            ->selectRaw('stocks.name, stocks.code, SUM(sale_items.quantity) as total_qty, SUM(sale_items.subtotal) as total_amount')
            ->whereBetween('sales.sale_date', [$start, $end])
            ->where('sales.status', '!=', 'cancelled')
            ->whereNull('sales.deleted_at')
            ->groupBy('stocks.name', 'stocks.code')
            ->orderByDesc('total_amount')
            ->limit($limit);

        if ($buId) $query->where('sales.business_unit_id', $buId);

        return $query->get()->toArray();
    }

    /**
     * Payable & receivable summary.
     */
    public static function getPayableReceivable(?int $buId): array
    {
        $payableQ = Payable::query()->whereIn('status', ['unpaid', 'partial']);
        $receivableQ = Receivable::query()->whereIn('status', ['unpaid', 'partial']);

        if ($buId) {
            $payableQ->where('business_unit_id', $buId);
            $receivableQ->where('business_unit_id', $buId);
        }

        $payableTotal = (float) $payableQ->sum(DB::raw('amount_due - paid_amount'));
        $payableCount = $payableQ->count();
        $payableOverdue = (clone $payableQ)->where('due_date', '<', now())->count();

        $receivableTotal = (float) $receivableQ->sum(DB::raw('amount - paid_amount'));
        $receivableCount = $receivableQ->count();
        $receivableOverdue = (clone $receivableQ)->where('due_date', '<', now())->count();

        return [
            'payable_total' => $payableTotal,
            'payable_count' => $payableCount,
            'payable_overdue' => $payableOverdue,
            'receivable_total' => $receivableTotal,
            'receivable_count' => $receivableCount,
            'receivable_overdue' => $receivableOverdue,
        ];
    }

    /**
     * Low stock alerts.
     */
    public static function getLowStock(?int $buId, int $limit = 5): array
    {
        $query = Stock::query()
            ->active()
            ->whereColumn('current_stock', '<=', 'min_stock')
            ->orderByRaw('current_stock - min_stock ASC')
            ->limit($limit);

        if ($buId) $query->where('business_unit_id', $buId);

        return $query->get(['id', 'code', 'name', 'current_stock', 'min_stock'])->toArray();
    }

    /**
     * Recent transactions (journals).
     */
    public static function getRecentTransactions(?int $buId, int $limit = 10): array
    {
        $query = JournalMaster::query()
            ->with(['period'])
            ->posted()
            ->orderByDesc('journal_date')
            ->orderByDesc('id')
            ->limit($limit);

        if ($buId) $query->where('business_unit_id', $buId);

        return $query->get()->map(function ($j) {
            return [
                'id' => $j->id,
                'journal_no' => $j->journal_no,
                'date' => $j->journal_date?->format('d/m/Y') ?? '-',
                'description' => $j->description ?? '-',
                'type' => $j->type,
                'amount' => (float) $j->total_debit,
            ];
        })->toArray();
    }

    /**
     * Bank + cash balances.
     */
    public static function getBankBalances(?int $buId): array
    {
        $bankQ = BankAccount::query()->with('bank');
        $cashQ = CashAccount::query();

        if ($buId) {
            $bankQ->where('business_unit_id', $buId);
            $cashQ->where('business_unit_id', $buId);
        }

        $banks = $bankQ->get()->map(fn($b) => [
            'name' => ($b->bank?->name ?? '') . ' - ' . $b->account_number,
            'balance' => (float) $b->current_balance,
            'type' => 'bank',
        ])->toArray();

        $cash = $cashQ->get()->map(fn($c) => [
            'name' => $c->name,
            'balance' => (float) $c->current_balance,
            'type' => 'cash',
        ])->toArray();

        return array_merge($banks, $cash);
    }
}
