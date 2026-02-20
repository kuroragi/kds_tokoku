<?php

namespace App\Services;

use App\Models\BusinessUnit;
use App\Models\Period;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\SaldoProvider;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class SalesService
{
    protected JournalService $journalService;

    public function __construct(?JournalService $journalService = null)
    {
        $this->journalService = $journalService ?? new JournalService();
    }

    // ==================== NUMBER GENERATION ====================

    public function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $year = date('Y');
        $month = date('m');

        $last = Sale::where('invoice_number', 'like', "{$prefix}/{$year}/{$month}/%")
            ->orderBy('invoice_number', 'desc')
            ->first();

        $number = $last ? ((int) substr($last->invoice_number, -4)) + 1 : 1;

        return "{$prefix}/{$year}/{$month}/" . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    // ==================== CREATE SALE ====================

    /**
     * Create a Sale.
     *
     * Payment types:
     * - cash: bayar tunai seluruhnya
     * - credit: piutang seluruhnya
     * - partial: bayar sebagian, sisanya piutang
     * - down_payment: DP diawal, sisanya piutang
     * - prepaid_deduction: potong pendapatan diterima dimuka
     */
    public function createSale(array $data, array $items): Sale
    {
        try {
            return DB::transaction(function () use ($data, $items) {
                $subtotal = 0;

                foreach ($items as &$item) {
                    $item['subtotal'] = ($item['quantity'] * $item['unit_price']) - ($item['discount'] ?? 0);
                    $subtotal += $item['subtotal'];
                }

                $data['subtotal'] = $subtotal;
                $data['grand_total'] = $subtotal - ($data['discount'] ?? 0) + ($data['tax'] ?? 0);
                $data['invoice_number'] = $data['invoice_number'] ?? $this->generateInvoiceNumber();
                $data['status'] = $data['status'] ?? 'confirmed';
                $data['sale_type'] = $data['sale_type'] ?? 'goods';

                // Calculate payment
                $this->calculatePaymentAmounts($data);

                $sale = Sale::create($data);

                foreach ($items as $item) {
                    $item['sale_id'] = $sale->id;
                    $item['item_type'] = $item['item_type'] ?? 'goods';
                    SaleItem::create($item);
                }

                // Process inventory changes (decrease stock / saldo)
                $this->processInventoryChanges($items);

                // Create journal
                $this->createSaleJournalEntry($sale, $data);

                $paymentSource = $data['payment_source'] ?? 'kas_utama';

                // Record initial payments
                if ($data['payment_type'] === 'cash') {
                    $this->recordInitialCashPayment($sale, $paymentSource);
                }

                if ($data['payment_type'] === 'partial' && ($data['paid_amount'] ?? 0) > 0) {
                    $this->recordInitialPartialPayment($sale, (float) $data['paid_amount'], $paymentSource);
                }

                if ($data['payment_type'] === 'down_payment' && ($data['down_payment_amount'] ?? 0) > 0) {
                    $this->recordDownPayment($sale, (float) $data['down_payment_amount'], $paymentSource);
                }

                // prepaid_deduction: recorded in journal only (no cash movement)

                return $sale->fresh(['items.stock', 'items.saldoProvider', 'customer', 'businessUnit', 'payments']);
            });
        } catch (Throwable $e) {
            throw ValidationException::withMessages([
                'sale' => 'Gagal membuat penjualan: ' . $e->getMessage(),
            ]);
        }
    }

    // ==================== PAYMENT ====================

    /**
     * Record a payment for a sale (pelunasan piutang).
     */
    public function recordPayment(Sale $sale, array $data): SalePayment
    {
        if ($sale->payment_status === 'paid') {
            throw ValidationException::withMessages([
                'payment' => 'Penjualan ini sudah lunas.',
            ]);
        }

        $remaining = (float) $sale->remaining_amount;
        $amount = (float) $data['amount'];

        if ($amount > $remaining) {
            throw ValidationException::withMessages([
                'amount' => "Jumlah pembayaran (Rp " . number_format($amount) . ") melebihi sisa piutang (Rp " . number_format($remaining) . ").",
            ]);
        }

        try {
            return DB::transaction(function () use ($sale, $data, $amount) {
                $data['sale_id'] = $sale->id;

                $payment = SalePayment::create($data);

                // Create payment journal
                $journal = $this->createPaymentJournalEntry($sale, $payment);
                if ($journal) {
                    $payment->update(['journal_master_id' => $journal->id]);
                }

                // Recalculate sale totals
                $sale->recalculatePayments();

                return $payment->fresh();
            });
        } catch (Throwable $e) {
            throw ValidationException::withMessages([
                'payment' => 'Gagal mencatat pembayaran: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Delete a payment.
     */
    public function deletePayment(SalePayment $payment): void
    {
        try {
            DB::transaction(function () use ($payment) {
                $sale = $payment->sale;
                $payment->delete();
                $sale->recalculatePayments();
            });
        } catch (Throwable $e) {
            throw ValidationException::withMessages([
                'payment' => 'Gagal menghapus pembayaran: ' . $e->getMessage(),
            ]);
        }
    }

    // ==================== CANCEL / DELETE ====================

    /**
     * Cancel a sale and reverse stock.
     */
    public function cancelSale(Sale $sale): Sale
    {
        if ($sale->status === 'cancelled') {
            throw ValidationException::withMessages([
                'status' => 'Penjualan sudah dibatalkan.',
            ]);
        }

        if ($sale->payments()->count() > 0) {
            throw ValidationException::withMessages([
                'status' => 'Tidak bisa membatalkan penjualan yang sudah ada pembayaran. Hapus pembayaran terlebih dahulu.',
            ]);
        }

        try {
            return DB::transaction(function () use ($sale) {
                // Reverse inventory changes per item type
                foreach ($sale->items as $item) {
                    $itemType = $item->item_type ?? 'goods';
                    if ($itemType === 'goods' && $item->stock_id) {
                        $stock = Stock::find($item->stock_id);
                        if ($stock) {
                            $stock->increment('current_stock', $item->quantity);
                        }
                    } elseif ($itemType === 'saldo' && $item->saldo_provider_id) {
                        $provider = SaldoProvider::find($item->saldo_provider_id);
                        if ($provider) {
                            $provider->increment('current_balance', $item->subtotal);
                        }
                    }
                }

                $sale->update([
                    'status' => 'cancelled',
                    'payment_status' => 'unpaid',
                    'paid_amount' => 0,
                    'remaining_amount' => $sale->grand_total,
                ]);

                return $sale->fresh();
            });
        } catch (Throwable $e) {
            throw ValidationException::withMessages([
                'sale' => 'Gagal membatalkan penjualan: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Delete sale (only draft).
     */
    public function deleteSale(Sale $sale): void
    {
        if ($sale->status !== 'draft') {
            throw ValidationException::withMessages([
                'status' => 'Hanya penjualan berstatus draft yang bisa dihapus.',
            ]);
        }

        DB::transaction(function () use ($sale) {
            $sale->items()->delete();
            $sale->payments()->delete();
            $sale->delete();
        });
    }

    // ==================== PRIVATE HELPERS ====================

    /**
     * Calculate payment amounts based on payment_type.
     */
    private function calculatePaymentAmounts(array &$data): void
    {
        $grandTotal = (float) $data['grand_total'];
        $paymentType = $data['payment_type'] ?? 'cash';

        switch ($paymentType) {
            case 'cash':
                $data['paid_amount'] = $grandTotal;
                $data['down_payment_amount'] = 0;
                $data['prepaid_deduction_amount'] = 0;
                $data['remaining_amount'] = 0;
                $data['payment_status'] = 'paid';
                break;

            case 'credit':
                $data['paid_amount'] = 0;
                $data['down_payment_amount'] = 0;
                $data['prepaid_deduction_amount'] = 0;
                $data['remaining_amount'] = $grandTotal;
                $data['payment_status'] = 'unpaid';
                break;

            case 'partial':
                $paidAmount = (float) ($data['paid_amount'] ?? 0);
                $data['paid_amount'] = $paidAmount;
                $data['down_payment_amount'] = 0;
                $data['prepaid_deduction_amount'] = 0;
                $data['remaining_amount'] = $grandTotal - $paidAmount;
                $data['payment_status'] = $paidAmount >= $grandTotal ? 'paid' : ($paidAmount > 0 ? 'partial' : 'unpaid');
                break;

            case 'down_payment':
                $dpAmount = (float) ($data['down_payment_amount'] ?? 0);
                $data['down_payment_amount'] = $dpAmount;
                $data['paid_amount'] = $dpAmount;
                $data['prepaid_deduction_amount'] = 0;
                $data['remaining_amount'] = $grandTotal - $dpAmount;
                $data['payment_status'] = $dpAmount >= $grandTotal ? 'paid' : ($dpAmount > 0 ? 'partial' : 'unpaid');
                break;

            case 'prepaid_deduction':
                $prepaidAmount = (float) ($data['prepaid_deduction_amount'] ?? 0);
                $cashPaid = (float) ($data['paid_amount'] ?? 0);
                $totalPaid = $prepaidAmount + $cashPaid;
                $data['prepaid_deduction_amount'] = $prepaidAmount;
                $data['paid_amount'] = $totalPaid;
                $data['down_payment_amount'] = 0;
                $data['remaining_amount'] = max(0, $grandTotal - $totalPaid);
                $data['payment_status'] = $totalPaid >= $grandTotal ? 'paid' : ($totalPaid > 0 ? 'partial' : 'unpaid');
                break;
        }
    }

    /**
     * Process inventory changes based on item type.
     * - goods: decrease stock quantity
     * - saldo: decrease saldo provider balance
     * - service: no inventory change (revenue only)
     */
    private function processInventoryChanges(array $items): void
    {
        foreach ($items as $item) {
            $itemType = $item['item_type'] ?? 'goods';

            if ($itemType === 'goods' && !empty($item['stock_id'])) {
                $stock = Stock::find($item['stock_id']);
                if ($stock) {
                    $stock->decrement('current_stock', $item['quantity']);
                }
            } elseif ($itemType === 'saldo' && !empty($item['saldo_provider_id'])) {
                $provider = SaldoProvider::find($item['saldo_provider_id']);
                if ($provider) {
                    $provider->decrement('current_balance', $item['subtotal']);
                }
            }
            // service: no inventory change
        }
    }

    /**
     * Create journal entry for a sale.
     *
     * Revenue journal:
     * - Debit: Kas/Piutang/Pendapatan Diterima Dimuka (depends on payment type)
     * - Credit: Pendapatan Utama / Pendapatan Jasa / Pendapatan Lain
     *
     * COGS journal (for goods & saldo):
     * - Debit: HPP
     * - Credit: Persediaan Barang / Persediaan Saldo
     */
    private function createSaleJournalEntry(Sale $sale, array $data = []): void
    {
        $period = Period::current()->open()->first();
        if (!$period) {
            return;
        }

        $businessUnit = $sale->businessUnit;
        if (!$businessUnit) {
            logger()->warning('Sale journal skipped: no business unit for sale #' . $sale->id);
            return;
        }

        $entries = [];
        $grandTotal = (float) $sale->grand_total;
        $paidAmount = 0;
        $creditAmount = $grandTotal; // piutang portion
        $prepaidAmount = 0;
        $paymentSource = $data['payment_source'] ?? $sale->payment_source ?? 'kas_utama';

        // Determine payment splits
        if ($sale->payment_type === 'cash') {
            $paidAmount = $grandTotal;
            $creditAmount = 0;
        } elseif ($sale->payment_type === 'partial') {
            $paidAmount = (float) $sale->paid_amount;
            $creditAmount = $grandTotal - $paidAmount;
        } elseif ($sale->payment_type === 'down_payment') {
            $paidAmount = (float) $sale->down_payment_amount;
            $creditAmount = $grandTotal - $paidAmount;
        } elseif ($sale->payment_type === 'prepaid_deduction') {
            $prepaidAmount = (float) $sale->prepaid_deduction_amount;
            $cashPaid = (float) ($data['paid_amount'] ?? 0) - $prepaidAmount;
            $paidAmount = max(0, $cashPaid);
            $creditAmount = max(0, $grandTotal - $prepaidAmount - $paidAmount);
        }

        // ── DEBIT SIDE (money coming in) ──

        // Cash/bank received
        if ($paidAmount > 0) {
            $paymentCoaCode = $this->resolveCoaCode($businessUnit, $paymentSource);
            if ($paymentCoaCode) {
                $paymentLabel = match ($paymentSource) {
                    'kas_kecil' => 'Penerimaan ke Kas Kecil',
                    'bank_utama' => 'Penerimaan via Bank',
                    default => 'Penerimaan Tunai',
                };
                $entries[] = [
                    'coa_code' => $paymentCoaCode,
                    'description' => $paymentLabel . ' - ' . $sale->invoice_number,
                    'debit' => $paidAmount,
                    'credit' => 0,
                ];
            }
        }

        // Piutang (receivable portion)
        if ($creditAmount > 0) {
            $piutangCoaCode = $this->resolveCoaCode($businessUnit, 'piutang_usaha');
            if ($piutangCoaCode) {
                $entries[] = [
                    'coa_code' => $piutangCoaCode,
                    'description' => 'Piutang Penjualan - ' . $sale->customer->name,
                    'debit' => $creditAmount,
                    'credit' => 0,
                ];
            }
        }

        // Prepaid deduction (potong pendapatan diterima dimuka)
        if ($prepaidAmount > 0) {
            $prepaidCoaCode = $this->resolveCoaCode($businessUnit, 'pendapatan_diterima_dimuka');
            if ($prepaidCoaCode) {
                $entries[] = [
                    'coa_code' => $prepaidCoaCode,
                    'description' => 'Potong Pendapatan Diterima Dimuka - ' . $sale->customer->name,
                    'debit' => $prepaidAmount,
                    'credit' => 0,
                ];
            }
        }

        // ── CREDIT SIDE (revenue) ──
        $sale->load('items');
        $goodsTotal = 0;
        $saldoTotal = 0;
        $serviceTotal = 0;

        foreach ($sale->items as $item) {
            $itemType = $item->item_type ?? 'goods';
            $amount = (float) $item->subtotal;

            match ($itemType) {
                'saldo' => $saldoTotal += $amount,
                'service' => $serviceTotal += $amount,
                default => $goodsTotal += $amount,
            };
        }

        $subtotal = (float) $sale->subtotal;
        $ratio = $subtotal > 0 ? $grandTotal / $subtotal : 1;

        if ($goodsTotal > 0) {
            $coaCode = $this->resolveCoaCode($businessUnit, 'pendapatan_utama');
            if ($coaCode) {
                $entries[] = [
                    'coa_code' => $coaCode,
                    'description' => 'Pendapatan Penjualan Barang - ' . $sale->customer->name,
                    'debit' => 0,
                    'credit' => round($goodsTotal * $ratio, 2),
                ];
            }
        }

        if ($saldoTotal > 0) {
            $coaCode = $this->resolveCoaCode($businessUnit, 'pendapatan_utama');
            if ($coaCode) {
                $entries[] = [
                    'coa_code' => $coaCode,
                    'description' => 'Pendapatan Penjualan Saldo - ' . $sale->customer->name,
                    'debit' => 0,
                    'credit' => round($saldoTotal * $ratio, 2),
                ];
            }
        }

        if ($serviceTotal > 0) {
            $coaCode = $this->resolveCoaCode($businessUnit, 'pendapatan_jasa');
            if ($coaCode) {
                $entries[] = [
                    'coa_code' => $coaCode,
                    'description' => 'Pendapatan Jasa - ' . $sale->customer->name,
                    'debit' => 0,
                    'credit' => round($serviceTotal * $ratio, 2),
                ];
            }
        }

        if (empty($entries)) {
            logger()->warning('Sale journal skipped: no COA mappings found for BU #' . $businessUnit->id);
            return;
        }

        try {
            $journal = $this->journalService->createJournalEntry([
                'journal_date' => $sale->sale_date->format('Y-m-d'),
                'reference' => $sale->invoice_number,
                'description' => 'Penjualan - ' . $sale->customer->name,
                'id_period' => $period->id,
                'type' => 'general',
                'status' => 'posted',
                'entries' => $entries,
            ]);

            $sale->update(['journal_master_id' => $journal->id]);
        } catch (\Exception $e) {
            logger()->warning('Failed to create sale journal: ' . $e->getMessage());
        }

        // ── HPP / COGS Journal (separate balanced entry) ──
        $this->createCOGSJournalEntry($sale, $period, $businessUnit, $ratio);
    }

    /**
     * Create COGS journal entry (HPP).
     * Debit: HPP
     * Credit: Persediaan Barang / Persediaan Saldo
     */
    private function createCOGSJournalEntry(Sale $sale, $period, BusinessUnit $businessUnit, float $ratio): void
    {
        $entries = [];
        $totalCogs = 0;

        foreach ($sale->items as $item) {
            $itemType = $item->item_type ?? 'goods';

            if ($itemType === 'goods' && $item->stock_id) {
                $stock = Stock::find($item->stock_id);
                if ($stock) {
                    $cogs = (float) $stock->buy_price * (float) $item->quantity;
                    $totalCogs += $cogs;
                }
            } elseif ($itemType === 'saldo' && $item->saldo_provider_id) {
                // For saldo, COGS = cost/purchase price of saldo
                $totalCogs += (float) $item->subtotal * 0.9; // approximate margin, or use actual cost
            }
            // service: no COGS
        }

        if ($totalCogs <= 0) {
            return;
        }

        // Debit HPP
        $hppCoaCode = $this->resolveCoaCode($businessUnit, 'hpp');
        $persediaanCoaCode = $this->resolveCoaCode($businessUnit, 'persediaan_barang');

        if (!$hppCoaCode || !$persediaanCoaCode) {
            return;
        }

        $entries[] = [
            'coa_code' => $hppCoaCode,
            'description' => 'HPP Penjualan - ' . $sale->customer->name,
            'debit' => round($totalCogs, 2),
            'credit' => 0,
        ];

        $entries[] = [
            'coa_code' => $persediaanCoaCode,
            'description' => 'Pengurangan Persediaan - ' . $sale->invoice_number,
            'debit' => 0,
            'credit' => round($totalCogs, 2),
        ];

        try {
            $this->journalService->createJournalEntry([
                'journal_date' => $sale->sale_date->format('Y-m-d'),
                'reference' => $sale->invoice_number,
                'description' => 'HPP Penjualan - ' . $sale->customer->name,
                'id_period' => $period->id,
                'type' => 'general',
                'status' => 'posted',
                'entries' => $entries,
            ]);
        } catch (\Exception $e) {
            logger()->warning('Failed to create COGS journal: ' . $e->getMessage());
        }
    }

    /**
     * Create payment journal (pelunasan piutang).
     * Debit: Kas/Bank (payment source)
     * Credit: Piutang Usaha
     */
    private function createPaymentJournalEntry(Sale $sale, SalePayment $payment)
    {
        $period = Period::current()->open()->first();
        if (!$period) {
            return null;
        }

        $businessUnit = $sale->businessUnit;
        if (!$businessUnit) {
            return null;
        }

        $paymentSource = $payment->payment_source ?? 'kas_utama';
        $piutangCoaCode = $this->resolveCoaCode($businessUnit, 'piutang_usaha');
        $paymentCoaCode = $this->resolveCoaCode($businessUnit, $paymentSource);

        if (!$piutangCoaCode || !$paymentCoaCode) {
            logger()->warning('Payment journal skipped: missing COA mapping for BU #' . $businessUnit->id);
            return null;
        }

        $paymentLabel = match ($paymentSource) {
            'kas_kecil' => 'Penerimaan ke Kas Kecil',
            'bank_utama' => 'Penerimaan via Bank',
            default => 'Penerimaan Tunai',
        };

        try {
            return $this->journalService->createJournalEntry([
                'journal_date' => $payment->payment_date->format('Y-m-d'),
                'reference' => $payment->reference_no ?? $sale->invoice_number,
                'description' => 'Penerimaan Piutang - ' . $sale->customer->name . ' (' . $sale->invoice_number . ')',
                'id_period' => $period->id,
                'type' => 'general',
                'status' => 'posted',
                'entries' => [
                    [
                        'coa_code' => $paymentCoaCode,
                        'description' => $paymentLabel . ' - ' . $sale->invoice_number,
                        'debit' => (float) $payment->amount,
                        'credit' => 0,
                    ],
                    [
                        'coa_code' => $piutangCoaCode,
                        'description' => 'Pelunasan Piutang - ' . $sale->customer->name,
                        'debit' => 0,
                        'credit' => (float) $payment->amount,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            logger()->warning('Failed to create sale payment journal: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Resolve COA code from BusinessUnit COA mapping.
     */
    private function resolveCoaCode(BusinessUnit $businessUnit, string $accountKey): ?string
    {
        $coa = $businessUnit->getCoaByKey($accountKey);
        return $coa?->code;
    }

    /**
     * Record immediate cash payment.
     */
    private function recordInitialCashPayment(Sale $sale, string $paymentSource = 'kas_utama'): void
    {
        $method = in_array($paymentSource, ['bank_utama']) ? 'bank_transfer' : 'cash';

        SalePayment::create([
            'sale_id' => $sale->id,
            'amount' => $sale->grand_total,
            'payment_date' => $sale->sale_date,
            'payment_method' => $method,
            'payment_source' => $paymentSource,
            'notes' => 'Pembayaran tunai saat penjualan',
        ]);
    }

    /**
     * Record partial initial payment.
     */
    private function recordInitialPartialPayment(Sale $sale, float $amount, string $paymentSource = 'kas_utama'): void
    {
        $method = in_array($paymentSource, ['bank_utama']) ? 'bank_transfer' : 'cash';

        SalePayment::create([
            'sale_id' => $sale->id,
            'amount' => $amount,
            'payment_date' => $sale->sale_date,
            'payment_method' => $method,
            'payment_source' => $paymentSource,
            'notes' => 'Pembayaran sebagian saat penjualan',
        ]);
    }

    /**
     * Record down payment.
     */
    private function recordDownPayment(Sale $sale, float $amount, string $paymentSource = 'kas_utama'): void
    {
        $method = in_array($paymentSource, ['bank_utama']) ? 'bank_transfer' : 'cash';

        SalePayment::create([
            'sale_id' => $sale->id,
            'amount' => $amount,
            'payment_date' => $sale->sale_date,
            'payment_method' => $method,
            'payment_source' => $paymentSource,
            'notes' => 'Uang muka (DP) penjualan',
        ]);
    }
}
