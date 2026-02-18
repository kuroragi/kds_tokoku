<?php

namespace App\Livewire\Saldo;

use App\Models\SaldoProduct;
use App\Models\SaldoProvider;
use App\Models\SaldoTransaction;
use App\Services\BusinessUnitService;
use App\Services\SaldoService;
use Livewire\Component;

class SaldoTransactionForm extends Component
{
    public bool $showModal = false;
    public ?int $transactionId = null;
    public bool $isEditing = false;

    // Fields
    public $business_unit_id = '';
    public $saldo_provider_id = '';
    public $saldo_product_id = '';
    public $customer_name = '';
    public $customer_phone = '';
    public $buy_price = 0;
    public $sell_price = 0;
    public $transaction_date = '';
    public $notes = '';

    protected $listeners = ['openSaldoTransactionModal', 'editSaldoTransaction'];

    public function openSaldoTransactionModal()
    {
        $this->resetForm();
        $this->business_unit_id = BusinessUnitService::getDefaultBusinessUnitId();
        $this->transaction_date = date('Y-m-d');
        $this->showModal = true;
    }

    public function editSaldoTransaction($id)
    {
        $transaction = SaldoTransaction::findOrFail($id);

        $this->transactionId = $transaction->id;
        $this->isEditing = true;
        $this->business_unit_id = $transaction->business_unit_id;
        $this->saldo_provider_id = $transaction->saldo_provider_id;
        $this->saldo_product_id = $transaction->saldo_product_id ?? '';
        $this->customer_name = $transaction->customer_name ?? '';
        $this->customer_phone = $transaction->customer_phone ?? '';
        $this->buy_price = $transaction->buy_price;
        $this->sell_price = $transaction->sell_price;
        $this->transaction_date = $transaction->transaction_date->format('Y-m-d');
        $this->notes = $transaction->notes ?? '';
        $this->showModal = true;
    }

    /**
     * When a product is selected, auto-fill buy/sell prices.
     */
    public function updatedSaldoProductId($value)
    {
        if ($value) {
            $product = SaldoProduct::find($value);
            if ($product) {
                $this->buy_price = $product->buy_price;
                $this->sell_price = $product->sell_price;
                if (!$this->saldo_provider_id && $product->saldo_provider_id) {
                    $this->saldo_provider_id = $product->saldo_provider_id;
                }
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
        $this->transactionId = null;
        $this->isEditing = false;
        $this->business_unit_id = '';
        $this->saldo_provider_id = '';
        $this->saldo_product_id = '';
        $this->customer_name = '';
        $this->customer_phone = '';
        $this->buy_price = 0;
        $this->sell_price = 0;
        $this->transaction_date = '';
        $this->notes = '';
        $this->resetValidation();
    }

    protected function rules(): array
    {
        return [
            'business_unit_id' => 'required|exists:business_units,id',
            'saldo_provider_id' => 'required|exists:saldo_providers,id',
            'saldo_product_id' => 'nullable|exists:saldo_products,id',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'buy_price' => 'required|numeric|min:0',
            'sell_price' => 'required|numeric|min:0',
            'transaction_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    protected $messages = [
        'business_unit_id.required' => 'Unit usaha wajib dipilih.',
        'saldo_provider_id.required' => 'Penyedia saldo wajib dipilih.',
        'buy_price.required' => 'Harga modal wajib diisi.',
        'sell_price.required' => 'Harga jual wajib diisi.',
        'transaction_date.required' => 'Tanggal transaksi wajib diisi.',
    ];

    public function save()
    {
        $this->business_unit_id = BusinessUnitService::resolveBusinessUnitId($this->business_unit_id);
        $this->validate();

        $data = [
            'business_unit_id' => $this->business_unit_id,
            'saldo_provider_id' => $this->saldo_provider_id,
            'saldo_product_id' => $this->saldo_product_id ?: null,
            'customer_name' => $this->customer_name ?: null,
            'customer_phone' => $this->customer_phone ?: null,
            'buy_price' => $this->buy_price,
            'sell_price' => $this->sell_price,
            'transaction_date' => $this->transaction_date,
            'notes' => $this->notes ?: null,
        ];

        $service = new SaldoService();

        if ($this->isEditing) {
            $oldTransaction = SaldoTransaction::findOrFail($this->transactionId);
            $service->deleteTransaction($oldTransaction);
            $service->createTransaction($data);
        } else {
            $service->createTransaction($data);
        }

        $action = $this->isEditing ? 'diperbarui' : 'dibuat';
        $this->dispatch('alert', type: 'success', message: "Transaksi saldo berhasil {$action}.");
        $this->dispatch('refreshSaldoTransactionList');
        $this->dispatch('refreshSaldoProviderList');
        $this->closeModal();
    }

    public function getUnitsProperty()
    {
        return BusinessUnitService::getAvailableUnits();
    }

    public function getAvailableProvidersProperty()
    {
        $query = SaldoProvider::active();
        if ($this->business_unit_id) {
            $query->where('business_unit_id', $this->business_unit_id);
        }
        return $query->orderBy('name')->get();
    }

    public function getAvailableProductsProperty()
    {
        $query = SaldoProduct::active();
        if ($this->business_unit_id) {
            $query->where('business_unit_id', $this->business_unit_id);
        }
        return $query->orderBy('name')->get();
    }

    public function render()
    {
        return view('livewire.saldo.saldo-transaction-form', [
            'units' => $this->units,
            'availableProviders' => $this->availableProviders,
            'availableProducts' => $this->availableProducts,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
