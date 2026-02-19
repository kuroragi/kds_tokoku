<?php

namespace App\Livewire\Purchase;

use App\Models\Purchase;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Stock;
use App\Models\Vendor;
use App\Services\BusinessUnitService;
use App\Services\PurchaseService;
use Livewire\Component;

class PurchaseForm extends Component
{
    public bool $showModal = false;

    // Mode
    public string $purchaseMode = 'direct'; // direct or from_po
    public $purchase_order_id = '';

    // Header fields
    public $business_unit_id = '';
    public $vendor_id = '';
    public $purchase_date = '';
    public $due_date = '';
    public $notes = '';
    public $discount = 0;
    public $tax = 0;

    // Payment
    public $payment_type = 'cash';
    public $paid_amount = 0;
    public $down_payment_amount = 0;

    // Items (dynamic rows for direct purchase)
    public array $items = [];

    // PO items for receiving
    public array $poItems = [];

    protected $listeners = ['openPurchaseModal', 'openPurchaseFromPO'];

    public function openPurchaseModal()
    {
        $this->resetForm();
        $this->purchaseMode = 'direct';
        $this->business_unit_id = BusinessUnitService::getDefaultBusinessUnitId();
        $this->purchase_date = date('Y-m-d');
        $this->addItem();
        $this->showModal = true;
    }

    public function openPurchaseFromPO($poId = null)
    {
        $this->resetForm();
        $this->purchaseMode = 'from_po';
        $this->business_unit_id = BusinessUnitService::getDefaultBusinessUnitId();
        $this->purchase_date = date('Y-m-d');

        if ($poId) {
            $this->purchase_order_id = $poId;
            $this->loadPOItems();
        }

        $this->showModal = true;
    }

    public function updatedPurchaseOrderId($value)
    {
        if ($value) {
            $this->loadPOItems();
        } else {
            $this->poItems = [];
        }
    }

    private function loadPOItems()
    {
        $po = PurchaseOrder::with('items.stock')->find($this->purchase_order_id);
        if (!$po) {
            $this->poItems = [];
            return;
        }

        $this->vendor_id = $po->vendor_id;
        $this->business_unit_id = $po->business_unit_id;
        $this->poItems = [];

        foreach ($po->items as $item) {
            $remaining = $item->remaining_quantity;
            if ($remaining <= 0) continue;

            $this->poItems[] = [
                'purchase_order_item_id' => $item->id,
                'stock_id' => $item->stock_id,
                'stock_name' => $item->stock->name ?? '-',
                'ordered_qty' => (float) $item->quantity,
                'received_qty' => (float) $item->received_quantity,
                'remaining_qty' => $remaining,
                'quantity' => $remaining, // Default: receive all remaining
                'unit_price' => (float) $item->unit_price,
                'discount' => 0,
                'notes' => '',
            ];
        }
    }

    public function addItem()
    {
        $this->items[] = [
            'stock_id' => '',
            'quantity' => 1,
            'unit_price' => 0,
            'discount' => 0,
            'notes' => '',
        ];
    }

    public function removeItem($index)
    {
        if (count($this->items) > 1) {
            unset($this->items[$index]);
            $this->items = array_values($this->items);
        }
    }

    public function updatedItems($value, $key)
    {
        $parts = explode('.', $key);
        if (count($parts) === 2 && $parts[1] === 'stock_id' && $value) {
            $stock = Stock::find($value);
            if ($stock) {
                $this->items[$parts[0]]['unit_price'] = (float) $stock->buy_price;
            }
        }
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->purchaseMode = 'direct';
        $this->purchase_order_id = '';
        $this->business_unit_id = '';
        $this->vendor_id = '';
        $this->purchase_date = '';
        $this->due_date = '';
        $this->notes = '';
        $this->discount = 0;
        $this->tax = 0;
        $this->payment_type = 'cash';
        $this->paid_amount = 0;
        $this->down_payment_amount = 0;
        $this->items = [];
        $this->poItems = [];
        $this->resetValidation();
    }

    protected function rules(): array
    {
        $rules = [
            'business_unit_id' => 'required|exists:business_units,id',
            'vendor_id' => 'required|exists:vendors,id',
            'purchase_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:purchase_date',
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'payment_type' => 'required|in:cash,credit,partial,down_payment',
        ];

        if ($this->payment_type === 'partial') {
            $rules['paid_amount'] = 'required|numeric|min:1';
        }

        if ($this->payment_type === 'down_payment') {
            $rules['down_payment_amount'] = 'required|numeric|min:1';
        }

        if ($this->purchaseMode === 'direct') {
            $rules['items'] = 'required|array|min:1';
            $rules['items.*.stock_id'] = 'required|exists:stocks,id';
            $rules['items.*.quantity'] = 'required|numeric|min:0.01';
            $rules['items.*.unit_price'] = 'required|numeric|min:0';
        } else {
            $rules['purchase_order_id'] = 'required|exists:purchase_orders,id';
            $rules['poItems'] = 'required|array|min:1';
            $rules['poItems.*.quantity'] = 'required|numeric|min:0.01';
        }

        return $rules;
    }

    protected $messages = [
        'business_unit_id.required' => 'Unit usaha wajib dipilih.',
        'vendor_id.required' => 'Vendor wajib dipilih.',
        'purchase_date.required' => 'Tanggal pembelian wajib diisi.',
        'payment_type.required' => 'Tipe pembayaran wajib dipilih.',
        'paid_amount.required' => 'Jumlah bayar wajib diisi untuk pembayaran sebagian.',
        'paid_amount.min' => 'Jumlah bayar minimal 1.',
        'down_payment_amount.required' => 'Jumlah DP wajib diisi.',
        'down_payment_amount.min' => 'Jumlah DP minimal 1.',
        'items.required' => 'Minimal 1 item harus diisi.',
        'items.*.stock_id.required' => 'Barang wajib dipilih.',
        'items.*.quantity.required' => 'Kuantitas wajib diisi.',
        'purchase_order_id.required' => 'Purchase Order wajib dipilih.',
    ];

    public function save()
    {
        $this->business_unit_id = BusinessUnitService::resolveBusinessUnitId($this->business_unit_id);
        $this->validate();

        $service = new PurchaseService();

        $data = [
            'business_unit_id' => $this->business_unit_id,
            'vendor_id' => $this->vendor_id,
            'purchase_date' => $this->purchase_date,
            'due_date' => $this->due_date ?: null,
            'notes' => $this->notes ?: null,
            'discount' => $this->discount ?: 0,
            'tax' => $this->tax ?: 0,
            'payment_type' => $this->payment_type,
            'paid_amount' => $this->paid_amount ?: 0,
            'down_payment_amount' => $this->down_payment_amount ?: 0,
        ];

        if ($this->purchaseMode === 'direct') {
            $service->createDirectPurchase($data, $this->items);
        } else {
            $po = PurchaseOrder::findOrFail($this->purchase_order_id);

            $receivedItems = [];
            foreach ($this->poItems as $item) {
                if (($item['quantity'] ?? 0) > 0) {
                    $receivedItems[] = [
                        'purchase_order_item_id' => $item['purchase_order_item_id'],
                        'quantity' => $item['quantity'],
                        'discount' => $item['discount'] ?? 0,
                        'notes' => $item['notes'] ?? null,
                    ];
                }
            }

            $service->createPurchaseFromPO($po, $data, $receivedItems);
        }

        $this->dispatch('alert', type: 'success', message: 'Pembelian berhasil disimpan.');
        $this->dispatch('refreshPurchaseList');
        $this->dispatch('refreshPurchaseOrderList');
        $this->closeModal();
    }

    // ─── Computed Properties ───

    public function getSubtotalProperty(): float
    {
        if ($this->purchaseMode === 'direct') {
            $subtotal = 0;
            foreach ($this->items as $item) {
                $subtotal += (($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0)) - ($item['discount'] ?? 0);
            }
            return $subtotal;
        }

        $subtotal = 0;
        foreach ($this->poItems as $item) {
            $subtotal += (($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0)) - ($item['discount'] ?? 0);
        }
        return $subtotal;
    }

    public function getGrandTotalProperty(): float
    {
        return $this->subtotal - ($this->discount ?: 0) + ($this->tax ?: 0);
    }

    public function getUnitsProperty()
    {
        return BusinessUnitService::getAvailableUnits();
    }

    public function getAvailableVendorsProperty()
    {
        $query = Vendor::active();
        if ($this->business_unit_id) {
            $query->byBusinessUnit($this->business_unit_id);
        }
        return $query->orderBy('name')->get();
    }

    public function getAvailableStocksProperty()
    {
        $query = Stock::active();
        if ($this->business_unit_id) {
            $query->byBusinessUnit($this->business_unit_id);
        }
        return $query->orderBy('name')->get();
    }

    public function getAvailablePOsProperty()
    {
        $query = PurchaseOrder::receivable();
        if ($this->business_unit_id) {
            $query->byBusinessUnit($this->business_unit_id);
        }
        return $query->with('vendor')->orderBy('po_date', 'desc')->get();
    }

    public function render()
    {
        return view('livewire.purchase.purchase-form', [
            'units' => $this->units,
            'availableVendors' => $this->availableVendors,
            'availableStocks' => $this->availableStocks,
            'availablePOs' => $this->availablePOs,
            'subtotal' => $this->subtotal,
            'grandTotal' => $this->grandTotal,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
