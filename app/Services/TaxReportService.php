<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\Sale;
use App\Models\TaxCalculation;
use App\Models\TaxInvoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TaxReportService
{
    /**
     * Get SPT Masa PPN summary for a given period.
     */
    public static function getSptMasaPPN(?int $buId, string $taxPeriod): array
    {
        $keluaranQ = TaxInvoice::query()->keluaran()->byPeriod($taxPeriod)->where('status', '!=', 'cancelled');
        $masukanQ = TaxInvoice::query()->masukan()->byPeriod($taxPeriod)->where('status', '!=', 'cancelled');

        if ($buId) {
            $keluaranQ->where('business_unit_id', $buId);
            $masukanQ->where('business_unit_id', $buId);
        }

        $keluaranDPP = (float) $keluaranQ->sum('dpp');
        $keluaranPPN = (float) $keluaranQ->sum('ppn');
        $keluaranCount = $keluaranQ->count();

        $masukanDPP = (float) $masukanQ->sum('dpp');
        $masukanPPN = (float) $masukanQ->sum('ppn');
        $masukanCount = $masukanQ->count();

        $ppnKurangBayar = $keluaranPPN - $masukanPPN;

        return [
            'period' => $taxPeriod,
            'keluaran_dpp' => $keluaranDPP,
            'keluaran_ppn' => $keluaranPPN,
            'keluaran_count' => $keluaranCount,
            'masukan_dpp' => $masukanDPP,
            'masukan_ppn' => $masukanPPN,
            'masukan_count' => $masukanCount,
            'ppn_kurang_bayar' => $ppnKurangBayar,
            'status' => $ppnKurangBayar > 0 ? 'Kurang Bayar' : ($ppnKurangBayar < 0 ? 'Lebih Bayar' : 'Nihil'),
        ];
    }

    /**
     * Get SPT Tahunan PPh Badan summary.
     */
    public static function getSptTahunan(?int $buId, int $year): array
    {
        $taxCalc = TaxCalculation::query()->forYear($year)->latest()->first();

        // Sales revenue for the year
        $saleQ = Sale::query()->whereYear('sale_date', $year)->where('status', '!=', 'cancelled');
        $purchaseQ = Purchase::query()->whereYear('purchase_date', $year)->where('status', '!=', 'cancelled');

        if ($buId) {
            $saleQ->where('business_unit_id', $buId);
            $purchaseQ->where('business_unit_id', $buId);
        }

        $totalRevenue = (float) $saleQ->sum('grand_total');
        $totalCost = (float) $purchaseQ->sum('grand_total');
        $grossProfit = $totalRevenue - $totalCost;

        // Monthly PPN data for the year context
        $monthlyPPN = [];
        for ($m = 1; $m <= 12; $m++) {
            $period = sprintf('%04d-%02d', $year, $m);
            $monthlyPPN[] = self::getSptMasaPPN($buId, $period);
        }

        $totalPPNKeluaran = array_sum(array_column($monthlyPPN, 'keluaran_ppn'));
        $totalPPNMasukan = array_sum(array_column($monthlyPPN, 'masukan_ppn'));

        return [
            'year' => $year,
            'total_revenue' => $totalRevenue,
            'total_cost' => $totalCost,
            'gross_profit' => $grossProfit,
            'tax_calculation' => $taxCalc ? [
                'commercial_profit' => (float) $taxCalc->commercial_profit,
                'fiscal_profit' => (float) $taxCalc->fiscal_profit,
                'taxable_income' => (float) $taxCalc->taxable_income,
                'tax_rate' => (float) $taxCalc->tax_rate,
                'tax_amount' => (float) $taxCalc->tax_amount,
                'status' => $taxCalc->status,
            ] : null,
            'total_ppn_keluaran' => $totalPPNKeluaran,
            'total_ppn_masukan' => $totalPPNMasukan,
            'monthly_ppn' => $monthlyPPN,
        ];
    }

    /**
     * Create a tax invoice (Faktur Pajak).
     */
    public static function createTaxInvoice(array $data): TaxInvoice
    {
        // Auto-calculate PPN if not provided
        if (empty($data['ppn']) && !empty($data['dpp'])) {
            $data['ppn'] = round($data['dpp'] * 0.11, 2); // PPN 11%
        }

        // Auto-set tax period if not provided
        if (empty($data['tax_period']) && !empty($data['invoice_date'])) {
            $data['tax_period'] = Carbon::parse($data['invoice_date'])->format('Y-m');
        }

        return TaxInvoice::create($data);
    }

    /**
     * Update a tax invoice.
     */
    public static function updateTaxInvoice(TaxInvoice $invoice, array $data): TaxInvoice
    {
        if (in_array($invoice->status, ['reported'])) {
            throw new \Exception('Faktur yang sudah dilaporkan tidak bisa diedit.');
        }

        if (empty($data['ppn']) && !empty($data['dpp'])) {
            $data['ppn'] = round($data['dpp'] * 0.11, 2);
        }
        if (empty($data['tax_period']) && !empty($data['invoice_date'])) {
            $data['tax_period'] = Carbon::parse($data['invoice_date'])->format('Y-m');
        }

        $invoice->update($data);
        return $invoice->fresh();
    }

    /**
     * Change status of a tax invoice.
     */
    public static function changeStatus(TaxInvoice $invoice, string $status): TaxInvoice
    {
        if (!array_key_exists($status, TaxInvoice::STATUSES)) {
            throw new \InvalidArgumentException("Invalid status: {$status}");
        }

        $invoice->update(['status' => $status]);
        return $invoice->fresh();
    }

    /**
     * Delete a tax invoice (only draft/cancelled).
     */
    public static function deleteTaxInvoice(TaxInvoice $invoice): void
    {
        if (!in_array($invoice->status, ['draft', 'cancelled'])) {
            throw new \Exception('Hanya faktur draft/batal yang bisa dihapus.');
        }
        $invoice->delete();
    }

    /**
     * Generate tax invoices from sales in a period.
     */
    public static function generateFromSales(int $buId, string $taxPeriod): int
    {
        $start = Carbon::parse($taxPeriod . '-01');
        $end = $start->copy()->endOfMonth();

        $sales = Sale::query()
            ->where('business_unit_id', $buId)
            ->whereBetween('sale_date', [$start, $end])
            ->where('status', '!=', 'cancelled')
            ->where('tax', '>', 0)
            ->whereDoesntHave('taxInvoices')
            ->get();

        $count = 0;
        foreach ($sales as $sale) {
            $dpp = (float) $sale->subtotal - (float) $sale->discount;
            self::createTaxInvoice([
                'business_unit_id' => $buId,
                'invoice_type' => 'keluaran',
                'invoice_date' => $sale->sale_date,
                'tax_period' => $taxPeriod,
                'partner_name' => $sale->customer?->name ?? 'Umum',
                'partner_npwp' => $sale->customer?->npwp ?? null,
                'dpp' => $dpp,
                'ppn' => (float) $sale->tax,
                'sale_id' => $sale->id,
                'status' => 'draft',
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * Generate tax invoices from purchases in a period.
     */
    public static function generateFromPurchases(int $buId, string $taxPeriod): int
    {
        $start = Carbon::parse($taxPeriod . '-01');
        $end = $start->copy()->endOfMonth();

        $purchases = Purchase::query()
            ->where('business_unit_id', $buId)
            ->whereBetween('purchase_date', [$start, $end])
            ->where('status', '!=', 'cancelled')
            ->where('tax', '>', 0)
            ->whereDoesntHave('taxInvoices')
            ->get();

        $count = 0;
        foreach ($purchases as $purchase) {
            $dpp = (float) $purchase->subtotal - (float) $purchase->discount;
            self::createTaxInvoice([
                'business_unit_id' => $buId,
                'invoice_type' => 'masukan',
                'invoice_date' => $purchase->purchase_date,
                'tax_period' => $taxPeriod,
                'partner_name' => $purchase->vendor?->name ?? 'Umum',
                'partner_npwp' => $purchase->vendor?->npwp ?? null,
                'dpp' => $dpp,
                'ppn' => (float) $purchase->tax,
                'purchase_id' => $purchase->id,
                'status' => 'draft',
            ]);
            $count++;
        }

        return $count;
    }
}
