<?php

namespace App\Services;

use App\Models\Period;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchasePayment;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class PurchaseService
{
    protected JournalService $journalService;

    public function __construct(?JournalService $journalService = null)
    {
        $this->journalService = $journalService ?? new JournalService();
    }

    // ==================== NUMBER GENERATION ====================

    public function generatePONumber(): string
    {
        $prefix = 'PO';
        $year = date('Y');
        $month = date('m');

        $last = PurchaseOrder::where('po_number', 'like', "{$prefix}/{$year}/{$month}/%")
            ->orderBy('po_number', 'desc')
            ->first();

        $number = $last ? ((int) substr($last->po_number, -4)) + 1 : 1;

        return "{$prefix}/{$year}/{$month}/" . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    public function generateInvoiceNumber(): string
    {
        $prefix = 'PUR';
        $year = date('Y');
        $month = date('m');

        $last = Purchase::where('invoice_number', 'like', "{$prefix}/{$year}/{$month}/%")
            ->orderBy('invoice_number', 'desc')
            ->first();

        $number = $last ? ((int) substr($last->invoice_number, -4)) + 1 : 1;

        return "{$prefix}/{$year}/{$month}/" . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    // ==================== PURCHASE ORDER ====================

    /**
     * Create a Purchase Order.
     */
    public function createPurchaseOrder(array $data, array $items): PurchaseOrder
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
                $data['po_number'] = $data['po_number'] ?? $this->generatePONumber();
                $data['status'] = $data['status'] ?? 'draft';

                $po = PurchaseOrder::create($data);

                foreach ($items as $item) {
                    $item['purchase_order_id'] = $po->id;
                    $item['received_quantity'] = 0;
                    PurchaseOrderItem::create($item);
                }

                return $po->fresh(['items.stock', 'vendor', 'businessUnit']);
            });
        } catch (Throwable $e) {
            throw ValidationException::withMessages([
                'purchase_order' => 'Gagal membuat Purchase Order: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Confirm a PO (change from draft to confirmed).
     */
    public function confirmPurchaseOrder(PurchaseOrder $po): PurchaseOrder
    {
        if ($po->status !== 'draft') {
            throw ValidationException::withMessages([
                'status' => 'Hanya PO berstatus draft yang bisa dikonfirmasi.',
            ]);
        }

        $po->update(['status' => 'confirmed']);

        return $po->fresh();
    }

    /**
     * Cancel a PO.
     */
    public function cancelPurchaseOrder(PurchaseOrder $po): PurchaseOrder
    {
        if (!in_array($po->status, ['draft', 'confirmed'])) {
            throw ValidationException::withMessages([
                'status' => 'PO tidak bisa dibatalkan karena sudah ada penerimaan barang.',
            ]);
        }

        $po->update(['status' => 'cancelled']);

        return $po->fresh();
    }

    /**
     * Delete PO (only draft).
     */
    public function deletePurchaseOrder(PurchaseOrder $po): void
    {
        if ($po->status !== 'draft') {
            throw ValidationException::withMessages([
                'status' => 'Hanya PO berstatus draft yang bisa dihapus.',
            ]);
        }

        DB::transaction(function () use ($po) {
            $po->items()->delete();
            $po->delete();
        });
    }

    // ==================== PURCHASE (DIRECT & FROM PO) ====================

    /**
     * Create a Direct Purchase (without PO).
     *
     * Payment types:
     * - cash: bayar tunai seluruhnya
     * - credit: hutang seluruhnya
     * - partial: bayar sebagian, sisanya hutang
     * - down_payment: DP diawal, sisanya hutang (saat barang datang, DP mengurangi hutang)
     */
    public function createDirectPurchase(array $data, array $items): Purchase
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

                // Calculate payment
                $this->calculatePaymentAmounts($data);

                $purchase = Purchase::create($data);

                foreach ($items as $item) {
                    $item['purchase_id'] = $purchase->id;
                    PurchaseItem::create($item);
                }

                // Increase stock
                $this->increaseStock($items);

                // Create journal
                $this->createPurchaseJournalEntry($purchase);

                // If cash payment, record payment immediately
                if ($data['payment_type'] === 'cash') {
                    $this->recordInitialCashPayment($purchase);
                }

                // If partial payment, record the partial payment
                if ($data['payment_type'] === 'partial' && ($data['paid_amount'] ?? 0) > 0) {
                    $this->recordInitialPartialPayment($purchase, (float) $data['paid_amount']);
                }

                // If down payment, the DP was already recorded; remaining is credit
                if ($data['payment_type'] === 'down_payment' && ($data['down_payment_amount'] ?? 0) > 0) {
                    $this->recordDownPayment($purchase, (float) $data['down_payment_amount']);
                }

                return $purchase->fresh(['items.stock', 'vendor', 'businessUnit', 'payments']);
            });
        } catch (Throwable $e) {
            throw ValidationException::withMessages([
                'purchase' => 'Gagal membuat pembelian: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Create a Purchase from an existing PO.
     */
    public function createPurchaseFromPO(PurchaseOrder $po, array $data, array $receivedItems): Purchase
    {
        if (!in_array($po->status, ['confirmed', 'partial_received'])) {
            throw ValidationException::withMessages([
                'purchase_order' => 'PO harus berstatus Dikonfirmasi atau Diterima Sebagian.',
            ]);
        }

        try {
            return DB::transaction(function () use ($po, $data, $receivedItems) {
                $data['purchase_order_id'] = $po->id;
                $data['business_unit_id'] = $po->business_unit_id;
                $data['vendor_id'] = $po->vendor_id;
                $data['invoice_number'] = $data['invoice_number'] ?? $this->generateInvoiceNumber();
                $data['status'] = 'confirmed';

                $subtotal = 0;
                $purchaseItems = [];

                foreach ($receivedItems as $received) {
                    $poItem = PurchaseOrderItem::findOrFail($received['purchase_order_item_id']);

                    // Validate quantity doesn't exceed remaining
                    $remaining = $poItem->remaining_quantity;
                    if ($received['quantity'] > $remaining) {
                        $stock = Stock::find($poItem->stock_id);
                        throw new \Exception("Kuantitas terima untuk {$stock->name} melebihi sisa PO ({$remaining}).");
                    }

                    $itemSubtotal = ($received['quantity'] * $poItem->unit_price) - ($received['discount'] ?? 0);
                    $subtotal += $itemSubtotal;

                    $purchaseItems[] = [
                        'stock_id' => $poItem->stock_id,
                        'purchase_order_item_id' => $poItem->id,
                        'quantity' => $received['quantity'],
                        'unit_price' => $poItem->unit_price,
                        'discount' => $received['discount'] ?? 0,
                        'subtotal' => $itemSubtotal,
                        'notes' => $received['notes'] ?? null,
                    ];

                    // Update PO item received_quantity
                    $poItem->increment('received_quantity', $received['quantity']);
                }

                $data['subtotal'] = $subtotal;
                $data['grand_total'] = $subtotal - ($data['discount'] ?? 0) + ($data['tax'] ?? 0);

                // Calculate payment amounts
                $this->calculatePaymentAmounts($data);

                $purchase = Purchase::create($data);

                foreach ($purchaseItems as $item) {
                    $item['purchase_id'] = $purchase->id;
                    PurchaseItem::create($item);
                }

                // Increase stock
                $this->increaseStock($purchaseItems);

                // Update PO status
                $this->updatePOStatus($po);

                // Create journal
                $this->createPurchaseJournalEntry($purchase);

                // Handle payment recording
                if ($data['payment_type'] === 'cash') {
                    $this->recordInitialCashPayment($purchase);
                } elseif ($data['payment_type'] === 'partial' && ($data['paid_amount'] ?? 0) > 0) {
                    $this->recordInitialPartialPayment($purchase, (float) $data['paid_amount']);
                } elseif ($data['payment_type'] === 'down_payment' && ($data['down_payment_amount'] ?? 0) > 0) {
                    $this->recordDownPayment($purchase, (float) $data['down_payment_amount']);
                }

                return $purchase->fresh(['items.stock', 'vendor', 'businessUnit', 'purchaseOrder', 'payments']);
            });
        } catch (Throwable $e) {
            throw ValidationException::withMessages([
                'purchase' => 'Gagal membuat pembelian dari PO: ' . $e->getMessage(),
            ]);
        }
    }

    // ==================== PURCHASE PAYMENT ====================

    /**
     * Record a payment for a purchase.
     */
    public function recordPayment(Purchase $purchase, array $data): PurchasePayment
    {
        if ($purchase->payment_status === 'paid') {
            throw ValidationException::withMessages([
                'payment' => 'Pembelian ini sudah lunas.',
            ]);
        }

        $remaining = (float) $purchase->remaining_amount;
        $amount = (float) $data['amount'];

        if ($amount > $remaining) {
            throw ValidationException::withMessages([
                'amount' => "Jumlah pembayaran (Rp " . number_format($amount) . ") melebihi sisa hutang (Rp " . number_format($remaining) . ").",
            ]);
        }

        try {
            return DB::transaction(function () use ($purchase, $data, $amount) {
                $data['purchase_id'] = $purchase->id;

                $payment = PurchasePayment::create($data);

                // Create payment journal
                $journal = $this->createPaymentJournalEntry($purchase, $payment);
                $payment->update(['journal_master_id' => $journal->id]);

                // Recalculate purchase totals
                $purchase->recalculatePayments();

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
    public function deletePayment(PurchasePayment $payment): void
    {
        try {
            DB::transaction(function () use ($payment) {
                $purchase = $payment->purchase;
                $payment->delete();
                $purchase->recalculatePayments();
            });
        } catch (Throwable $e) {
            throw ValidationException::withMessages([
                'payment' => 'Gagal menghapus pembayaran: ' . $e->getMessage(),
            ]);
        }
    }

    // ==================== CANCEL / DELETE PURCHASE ====================

    /**
     * Cancel a purchase and reverse stock.
     */
    public function cancelPurchase(Purchase $purchase): Purchase
    {
        if ($purchase->status === 'cancelled') {
            throw ValidationException::withMessages([
                'status' => 'Pembelian sudah dibatalkan.',
            ]);
        }

        if ($purchase->payments()->count() > 0) {
            throw ValidationException::withMessages([
                'status' => 'Tidak bisa membatalkan pembelian yang sudah ada pembayaran. Hapus pembayaran terlebih dahulu.',
            ]);
        }

        try {
            return DB::transaction(function () use ($purchase) {
                // Reverse stock
                foreach ($purchase->items as $item) {
                    $stock = Stock::find($item->stock_id);
                    if ($stock) {
                        $stock->decrement('current_stock', $item->quantity);
                    }
                }

                // If from PO, reverse received quantities
                if ($purchase->purchase_order_id) {
                    foreach ($purchase->items as $item) {
                        if ($item->purchase_order_item_id) {
                            $poItem = PurchaseOrderItem::find($item->purchase_order_item_id);
                            if ($poItem) {
                                $poItem->decrement('received_quantity', $item->quantity);
                            }
                        }
                    }
                    $this->updatePOStatus($purchase->purchaseOrder);
                }

                $purchase->update([
                    'status' => 'cancelled',
                    'payment_status' => 'unpaid',
                    'paid_amount' => 0,
                    'remaining_amount' => $purchase->grand_total,
                ]);

                return $purchase->fresh();
            });
        } catch (Throwable $e) {
            throw ValidationException::withMessages([
                'purchase' => 'Gagal membatalkan pembelian: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Delete purchase (only draft).
     */
    public function deletePurchase(Purchase $purchase): void
    {
        if ($purchase->status !== 'draft') {
            throw ValidationException::withMessages([
                'status' => 'Hanya pembelian berstatus draft yang bisa dihapus.',
            ]);
        }

        DB::transaction(function () use ($purchase) {
            $purchase->items()->delete();
            $purchase->payments()->delete();
            $purchase->delete();
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
                $data['remaining_amount'] = 0;
                $data['payment_status'] = 'paid';
                break;

            case 'credit':
                $data['paid_amount'] = 0;
                $data['down_payment_amount'] = 0;
                $data['remaining_amount'] = $grandTotal;
                $data['payment_status'] = 'unpaid';
                break;

            case 'partial':
                $paidAmount = (float) ($data['paid_amount'] ?? 0);
                $data['paid_amount'] = $paidAmount;
                $data['down_payment_amount'] = 0;
                $data['remaining_amount'] = $grandTotal - $paidAmount;
                $data['payment_status'] = $paidAmount >= $grandTotal ? 'paid' : ($paidAmount > 0 ? 'partial' : 'unpaid');
                break;

            case 'down_payment':
                $dpAmount = (float) ($data['down_payment_amount'] ?? 0);
                $data['down_payment_amount'] = $dpAmount;
                $data['paid_amount'] = $dpAmount;
                $data['remaining_amount'] = $grandTotal - $dpAmount;
                $data['payment_status'] = $dpAmount >= $grandTotal ? 'paid' : ($dpAmount > 0 ? 'partial' : 'unpaid');
                break;
        }
    }

    /**
     * Increase stock quantities for purchased items.
     */
    private function increaseStock(array $items): void
    {
        foreach ($items as $item) {
            $stock = Stock::find($item['stock_id']);
            if ($stock) {
                $stock->increment('current_stock', $item['quantity']);
            }
        }
    }

    /**
     * Update PO status based on received quantities.
     */
    private function updatePOStatus(PurchaseOrder $po): void
    {
        $po->refresh();

        if ($po->isFullyReceived()) {
            $po->update(['status' => 'received']);
        } else {
            $hasAnyReceived = $po->items->contains(fn ($item) => $item->received_quantity > 0);
            if ($hasAnyReceived) {
                $po->update(['status' => 'partial_received']);
            }
        }
    }

    /**
     * Create journal entry for a purchase.
     *
     * Debit: Persediaan Barang / Pembelian
     * Credit: Kas (if cash) / Hutang Dagang (if credit/partial/dp)
     */
    private function createPurchaseJournalEntry(Purchase $purchase): void
    {
        $period = Period::current()->open()->first();
        if (!$period) {
            return; // Skip journal if no open period
        }

        $entries = [];
        $grandTotal = (float) $purchase->grand_total;
        $paidAmount = 0;
        $creditAmount = $grandTotal;

        // Determine initial payment
        if ($purchase->payment_type === 'cash') {
            $paidAmount = $grandTotal;
            $creditAmount = 0;
        } elseif ($purchase->payment_type === 'partial') {
            $paidAmount = (float) $purchase->paid_amount;
            $creditAmount = $grandTotal - $paidAmount;
        } elseif ($purchase->payment_type === 'down_payment') {
            $paidAmount = (float) $purchase->down_payment_amount;
            $creditAmount = $grandTotal - $paidAmount;
        }

        // Debit: Persediaan / Pembelian
        $entries[] = [
            'coa_code' => '1301', // Persediaan Barang
            'description' => 'Pembelian - ' . $purchase->vendor->name,
            'debit' => $grandTotal,
            'credit' => 0,
        ];

        // Credit: Kas (paid portion)
        if ($paidAmount > 0) {
            $entries[] = [
                'coa_code' => '1101', // Kas
                'description' => 'Pembayaran Tunai - ' . $purchase->invoice_number,
                'debit' => 0,
                'credit' => $paidAmount,
            ];
        }

        // Credit: Hutang Dagang (unpaid portion)
        if ($creditAmount > 0) {
            $entries[] = [
                'coa_code' => '2101', // Hutang Dagang
                'description' => 'Hutang Pembelian - ' . $purchase->vendor->name,
                'debit' => 0,
                'credit' => $creditAmount,
            ];
        }

        try {
            $journal = $this->journalService->createJournalEntry([
                'journal_date' => $purchase->purchase_date->format('Y-m-d'),
                'reference' => $purchase->invoice_number,
                'description' => 'Pembelian ' . ($purchase->isDirect() ? 'Langsung' : 'dari PO') . ' - ' . $purchase->vendor->name,
                'id_period' => $period->id,
                'type' => 'general',
                'status' => 'posted',
                'entries' => $entries,
            ]);

            $purchase->update(['journal_master_id' => $journal->id]);
        } catch (\Exception $e) {
            // Log but don't fail the purchase if journal creation fails
            logger()->warning('Failed to create purchase journal: ' . $e->getMessage());
        }
    }

    /**
     * Create journal for a payment.
     * Debit: Hutang Dagang
     * Credit: Kas
     */
    private function createPaymentJournalEntry(Purchase $purchase, PurchasePayment $payment)
    {
        $period = Period::current()->open()->first();
        if (!$period) {
            return null;
        }

        try {
            return $this->journalService->createJournalEntry([
                'journal_date' => $payment->payment_date->format('Y-m-d'),
                'reference' => $payment->reference_no ?? $purchase->invoice_number,
                'description' => 'Pembayaran Hutang - ' . $purchase->vendor->name . ' (' . $purchase->invoice_number . ')',
                'id_period' => $period->id,
                'type' => 'general',
                'status' => 'posted',
                'entries' => [
                    [
                        'coa_code' => '2101', // Hutang Dagang
                        'description' => 'Pelunasan Hutang - ' . $purchase->vendor->name,
                        'debit' => (float) $payment->amount,
                        'credit' => 0,
                    ],
                    [
                        'coa_code' => '1101', // Kas
                        'description' => 'Pembayaran - ' . $purchase->invoice_number,
                        'debit' => 0,
                        'credit' => (float) $payment->amount,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            logger()->warning('Failed to create payment journal: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Record immediate cash payment.
     */
    private function recordInitialCashPayment(Purchase $purchase): void
    {
        PurchasePayment::create([
            'purchase_id' => $purchase->id,
            'amount' => $purchase->grand_total,
            'payment_date' => $purchase->purchase_date,
            'payment_method' => 'cash',
            'notes' => 'Pembayaran tunai saat pembelian',
        ]);
    }

    /**
     * Record partial initial payment.
     */
    private function recordInitialPartialPayment(Purchase $purchase, float $amount): void
    {
        PurchasePayment::create([
            'purchase_id' => $purchase->id,
            'amount' => $amount,
            'payment_date' => $purchase->purchase_date,
            'payment_method' => 'cash',
            'notes' => 'Pembayaran sebagian saat pembelian',
        ]);
    }

    /**
     * Record down payment.
     */
    private function recordDownPayment(Purchase $purchase, float $amount): void
    {
        PurchasePayment::create([
            'purchase_id' => $purchase->id,
            'amount' => $amount,
            'payment_date' => $purchase->purchase_date,
            'payment_method' => 'cash',
            'notes' => 'Uang muka (DP) pembelian',
        ]);
    }
}
