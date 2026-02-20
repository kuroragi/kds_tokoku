<?php

namespace App\Livewire\Sales;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\SaldoProvider;
use App\Models\Stock;
use App\Services\BusinessUnitService;
use App\Services\SalesService;
use Livewire\Component;

class SaleForm extends Component
{
    public bool $showModal = false;

    // Header fields
    public $business_unit_id = '';
    public $customer_id = '';
    public $sale_type = 'goods'; // goods, saldo, service, mix
    public $sale_date = '';
    public $due_date = '';
    public $notes = '';
    public $discount = 0;
    public $tax = 0;

    // Payment
    public $payment_type = 'cash';
    public $payment_source = 'kas_utama';
    public $paid_amount = 0;
    public $down_payment_amount = 0;
    public $prepaid_deduction_amount = 0;

    // Items (dynamic rows)
    public array $items = [];

    protected $listeners = ['openSaleModal'];

    public function openSaleModal()
    {
        $this->resetForm();
        $this->business_unit_id = BusinessUnitService::getDefaultBusinessUnitId();
        $this->sale_date = date('Y-m-d');
        $this->addItem();
        $this->showModal = true;
    }

    public function addItem()
    {
        $defaultType = in_array($this->sale_type, ['goods', 'saldo', 'service'])
            ? $this->sale_type
            : 'goods';

        $this->items[] = [
            'item_type' => $defaultType,
            'stock_id' => '',
            'saldo_provider_id' => '',
            'description' => '',
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
        if (count($parts) === 2) {
            $idx = $parts[0];
            $field = $parts[1];

            if ($field === 'stock_id' && $value) {
                $stock = Stock::find($value);
                if ($stock) {
                    $this->items[$idx]['unit_price'] = (float) $stock->sell_price;
                }
            }

            if ($field === 'item_type') {
                $this->items[$idx]['stock_id'] = '';
                $this->items[$idx]['saldo_provider_id'] = '';
                $this->items[$idx]['description'] = '';
                $this->items[$idx]['unit_price'] = 0;
            }
        }
    }

    public function updatedSaleType($value)
    {
        if (in_array($value, ['goods', 'saldo', 'service'])) {
            foreach ($this->items as $idx => $item) {
                $this->items[$idx]['item_type'] = $value;
                $this->items[$idx]['stock_id'] = '';
                $this->items[$idx]['saldo_provider_id'] = '';
                $this->items[$idx]['description'] = '';
                $this->items[$idx]['unit_price'] = 0;
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
        $this->business_unit_id = '';
        $this->customer_id = '';
        $this->sale_type = 'goods';
        $this->sale_date = '';
        $this->due_date = '';
        $this->notes = '';
        $this->discount = 0;
        $this->tax = 0;
        $this->payment_type = 'cash';
        $this->payment_source = 'kas_utama';
        $this->paid_amount = 0;
        $this->down_payment_amount = 0;
        $this->prepaid_deduction_amount = 0;
        $this->items = [];
        $this->resetValidation();
    }

    protected function rules(): array
    {
        $rules = [
            'business_unit_id' => 'required|exists:business_units,id',
            'customer_id' => 'required|exists:customers,id',
            'sale_type' => 'required|in:goods,saldo,service,mix',
            'sale_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:sale_date',
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'payment_type' => 'required|in:cash,credit,partial,down_payment,prepaid_deduction',
            'payment_source' => 'required_unless:payment_type,credit|in:kas_utama,kas_kecil,bank_utama',
            'items' => 'required|array|min:1',
            'items.*.item_type' => 'required|in:goods,saldo,service',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ];

        if ($this->payment_type === 'partial') {
            $rules['paid_amount'] = 'required|numeric|min:1';
        }

        if ($this->payment_type === 'down_payment') {
            $rules['down_payment_amount'] = 'required|numeric|min:1';
        }

        if ($this->payment_type === 'prepaid_deduction') {
            $rules['prepaid_deduction_amount'] = 'required|numeric|min:1';
        }

        // Conditional validation per item type
        foreach ($this->items as $idx => $item) {
            $itemType = $item['item_type'] ?? 'goods';
            if ($itemType === 'goods') {
                $rules["items.{$idx}.stock_id"] = 'required|exists:stocks,id';
            } elseif ($itemType === 'saldo') {
                $rules["items.{$idx}.saldo_provider_id"] = 'required|exists:saldo_providers,id';
                $rules["items.{$idx}.description"] = 'required|string|max:255';
            } elseif ($itemType === 'service') {
                $rules["items.{$idx}.description"] = 'required|string|max:255';
            }
        }

        return $rules;
    }

    protected $messages = [
        'business_unit_id.required' => 'Unit usaha wajib dipilih.',
        'customer_id.required' => 'Pelanggan wajib dipilih.',
        'sale_date.required' => 'Tanggal penjualan wajib diisi.',
        'sale_type.required' => 'Jenis penjualan wajib dipilih.',
        'payment_type.required' => 'Tipe pembayaran wajib dipilih.',
        'payment_source.required_unless' => 'Sumber pembayaran wajib dipilih.',
        'paid_amount.required' => 'Jumlah bayar wajib diisi untuk pembayaran sebagian.',
        'paid_amount.min' => 'Jumlah bayar minimal 1.',
        'down_payment_amount.required' => 'Jumlah DP wajib diisi.',
        'down_payment_amount.min' => 'Jumlah DP minimal 1.',
        'prepaid_deduction_amount.required' => 'Jumlah potongan pendapatan diterima dimuka wajib diisi.',
        'prepaid_deduction_amount.min' => 'Jumlah potongan minimal 1.',
        'items.required' => 'Minimal 1 item harus diisi.',
        'items.*.stock_id.required' => 'Barang wajib dipilih.',
        'items.*.saldo_provider_id.required' => 'Provider saldo wajib dipilih.',
        'items.*.description.required' => 'Deskripsi wajib diisi.',
        'items.*.quantity.required' => 'Kuantitas wajib diisi.',
        'items.*.item_type.required' => 'Jenis item wajib dipilih.',
    ];

    public function save()
    {
        $this->business_unit_id = BusinessUnitService::resolveBusinessUnitId($this->business_unit_id);
        $this->validate();

        $service = new SalesService();

        $data = [
            'business_unit_id' => $this->business_unit_id,
            'customer_id' => $this->customer_id,
            'sale_type' => $this->sale_type,
            'sale_date' => $this->sale_date,
            'due_date' => $this->due_date ?: null,
            'notes' => $this->notes ?: null,
            'discount' => $this->discount ?: 0,
            'tax' => $this->tax ?: 0,
            'payment_type' => $this->payment_type,
            'payment_source' => $this->payment_type !== 'credit' ? $this->payment_source : null,
            'paid_amount' => $this->paid_amount ?: 0,
            'down_payment_amount' => $this->down_payment_amount ?: 0,
            'prepaid_deduction_amount' => $this->prepaid_deduction_amount ?: 0,
        ];

        $service->createSale($data, $this->items);

        $this->dispatch('alert', type: 'success', message: 'Penjualan berhasil disimpan.');
        $this->dispatch('refreshSaleList');
        $this->closeModal();
    }

    // ─── Computed Properties ───

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

    public function getAvailableCustomersProperty()
    {
        $query = Customer::active();
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

    public function getAvailableSaldoProvidersProperty()
    {
        $query = SaldoProvider::active();
        if ($this->business_unit_id) {
            $query->byBusinessUnit($this->business_unit_id);
        }
        return $query->orderBy('name')->get();
    }

    public function render()
    {
        return view('livewire.sales.sale-form', [
            'units' => $this->units,
            'availableCustomers' => $this->availableCustomers,
            'availableStocks' => $this->availableStocks,
            'availableSaldoProviders' => $this->availableSaldoProviders,
            'saleTypes' => Sale::SALE_TYPES,
            'itemTypes' => \App\Models\SaleItem::ITEM_TYPES,
            'subtotal' => $this->subtotal,
            'grandTotal' => $this->grandTotal,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
