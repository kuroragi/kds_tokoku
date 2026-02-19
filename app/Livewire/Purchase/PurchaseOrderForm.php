<?php

namespace App\Livewire\Purchase;

use App\Models\PurchaseOrder;
use App\Models\Stock;
use App\Models\Vendor;
use App\Services\BusinessUnitService;
use App\Services\PurchaseService;
use Livewire\Component;

class PurchaseOrderForm extends Component
{
    public bool $showModal = false;
    public bool $isEditing = false;
    public ?int $poId = null;

    // Header fields
    public $business_unit_id = '';
    public $vendor_id = '';
    public $po_date = '';
    public $expected_date = '';
    public $notes = '';
    public $discount = 0;
    public $tax = 0;

    // Items (dynamic rows)
    public array $items = [];

    protected $listeners = ['openPurchaseOrderModal', 'editPurchaseOrder'];

    public function openPurchaseOrderModal()
    {
        $this->resetForm();
        $this->business_unit_id = BusinessUnitService::getDefaultBusinessUnitId();
        $this->po_date = date('Y-m-d');
        $this->addItem();
        $this->showModal = true;
    }

    public function editPurchaseOrder($id)
    {
        $po = PurchaseOrder::with('items')->findOrFail($id);

        if ($po->status !== 'draft') {
            $this->dispatch('alert', type: 'error', message: 'Hanya PO berstatus draft yang bisa diedit.');
            return;
        }

        $this->poId = $po->id;
        $this->isEditing = true;
        $this->business_unit_id = $po->business_unit_id;
        $this->vendor_id = $po->vendor_id;
        $this->po_date = $po->po_date->format('Y-m-d');
        $this->expected_date = $po->expected_date?->format('Y-m-d') ?? '';
        $this->notes = $po->notes ?? '';
        $this->discount = $po->discount;
        $this->tax = $po->tax;

        $this->items = [];
        foreach ($po->items as $item) {
            $this->items[] = [
                'stock_id' => $item->stock_id,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'discount' => $item->discount,
                'notes' => $item->notes ?? '',
            ];
        }

        $this->showModal = true;
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
        // auto-fill unit_price when stock is selected
        $parts = explode('.', $key);
        if (count($parts) === 2 && $parts[1] === 'stock_id' && $value) {
            $stock = Stock::find($value);
            if ($stock) {
                $index = $parts[0];
                $this->items[$index]['unit_price'] = (float) $stock->buy_price;
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
        $this->poId = null;
        $this->isEditing = false;
        $this->business_unit_id = '';
        $this->vendor_id = '';
        $this->po_date = '';
        $this->expected_date = '';
        $this->notes = '';
        $this->discount = 0;
        $this->tax = 0;
        $this->items = [];
        $this->resetValidation();
    }

    protected function rules(): array
    {
        return [
            'business_unit_id' => 'required|exists:business_units,id',
            'vendor_id' => 'required|exists:vendors,id',
            'po_date' => 'required|date',
            'expected_date' => 'nullable|date|after_or_equal:po_date',
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.stock_id' => 'required|exists:stocks,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
        ];
    }

    protected $messages = [
        'business_unit_id.required' => 'Unit usaha wajib dipilih.',
        'vendor_id.required' => 'Vendor wajib dipilih.',
        'po_date.required' => 'Tanggal PO wajib diisi.',
        'items.required' => 'Minimal 1 item harus diisi.',
        'items.*.stock_id.required' => 'Barang wajib dipilih.',
        'items.*.quantity.required' => 'Kuantitas wajib diisi.',
        'items.*.quantity.min' => 'Kuantitas minimal 0.01.',
        'items.*.unit_price.required' => 'Harga satuan wajib diisi.',
    ];

    public function save()
    {
        $this->business_unit_id = BusinessUnitService::resolveBusinessUnitId($this->business_unit_id);
        $this->validate();

        $data = [
            'business_unit_id' => $this->business_unit_id,
            'vendor_id' => $this->vendor_id,
            'po_date' => $this->po_date,
            'expected_date' => $this->expected_date ?: null,
            'notes' => $this->notes ?: null,
            'discount' => $this->discount ?: 0,
            'tax' => $this->tax ?: 0,
        ];

        $service = new PurchaseService();

        if ($this->isEditing) {
            $po = PurchaseOrder::findOrFail($this->poId);
            $service->deletePurchaseOrder($po);
        }

        $service->createPurchaseOrder($data, $this->items);

        $action = $this->isEditing ? 'diperbarui' : 'dibuat';
        $this->dispatch('alert', type: 'success', message: "Purchase Order berhasil {$action}.");
        $this->dispatch('refreshPurchaseOrderList');
        $this->closeModal();
    }

    public function getSubtotalProperty(): float
    {
        $subtotal = 0;
        foreach ($this->items as $item) {
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

    public function render()
    {
        return view('livewire.purchase.purchase-order-form', [
            'units' => $this->units,
            'availableVendors' => $this->availableVendors,
            'availableStocks' => $this->availableStocks,
            'subtotal' => $this->subtotal,
            'grandTotal' => $this->grandTotal,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
