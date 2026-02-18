<?php

namespace App\Livewire\Saldo;

use App\Models\SaldoProduct;
use App\Models\SaldoProvider;
use App\Services\BusinessUnitService;
use Illuminate\Validation\Rule;
use Livewire\Component;

class SaldoProductForm extends Component
{
    public bool $showModal = false;
    public ?int $productId = null;
    public bool $isEditing = false;

    // Fields
    public $business_unit_id = '';
    public $code = '';
    public $name = '';
    public $saldo_provider_id = '';
    public $buy_price = 0;
    public $sell_price = 0;
    public $description = '';
    public $is_active = true;

    protected $listeners = ['openSaldoProductModal', 'editSaldoProduct'];

    public function openSaldoProductModal()
    {
        $this->resetForm();
        $this->business_unit_id = BusinessUnitService::getDefaultBusinessUnitId();
        $this->showModal = true;
    }

    public function editSaldoProduct($id)
    {
        $product = SaldoProduct::findOrFail($id);

        $this->productId = $product->id;
        $this->isEditing = true;
        $this->business_unit_id = $product->business_unit_id;
        $this->code = $product->code;
        $this->name = $product->name;
        $this->saldo_provider_id = $product->saldo_provider_id ?? '';
        $this->buy_price = $product->buy_price;
        $this->sell_price = $product->sell_price;
        $this->description = $product->description ?? '';
        $this->is_active = $product->is_active;
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->productId = null;
        $this->isEditing = false;
        $this->business_unit_id = '';
        $this->code = '';
        $this->name = '';
        $this->saldo_provider_id = '';
        $this->buy_price = 0;
        $this->sell_price = 0;
        $this->description = '';
        $this->is_active = true;
        $this->resetValidation();
    }

    protected function rules(): array
    {
        return [
            'business_unit_id' => 'required|exists:business_units,id',
            'code' => [
                'required', 'string', 'max:20',
                Rule::unique('saldo_products', 'code')
                    ->where('business_unit_id', $this->business_unit_id)
                    ->ignore($this->productId),
            ],
            'name' => 'required|string|max:255',
            'saldo_provider_id' => 'nullable|exists:saldo_providers,id',
            'buy_price' => 'required|numeric|min:0',
            'sell_price' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ];
    }

    protected $messages = [
        'business_unit_id.required' => 'Unit usaha wajib dipilih.',
        'code.required' => 'Kode produk wajib diisi.',
        'code.unique' => 'Kode produk sudah digunakan pada unit usaha ini.',
        'name.required' => 'Nama produk wajib diisi.',
        'buy_price.required' => 'Harga modal wajib diisi.',
        'buy_price.min' => 'Harga modal tidak boleh negatif.',
        'sell_price.required' => 'Harga jual wajib diisi.',
        'sell_price.min' => 'Harga jual tidak boleh negatif.',
    ];

    public function save()
    {
        $this->business_unit_id = BusinessUnitService::resolveBusinessUnitId($this->business_unit_id);
        $this->validate();

        $data = [
            'business_unit_id' => $this->business_unit_id,
            'code' => $this->code,
            'name' => $this->name,
            'saldo_provider_id' => $this->saldo_provider_id ?: null,
            'buy_price' => $this->buy_price,
            'sell_price' => $this->sell_price,
            'description' => $this->description ?: null,
            'is_active' => $this->is_active,
        ];

        if ($this->isEditing) {
            $product = SaldoProduct::findOrFail($this->productId);
            $product->update($data);
        } else {
            SaldoProduct::create($data);
        }

        $action = $this->isEditing ? 'diperbarui' : 'dibuat';
        $this->dispatch('alert', type: 'success', message: "Produk saldo '{$this->name}' berhasil {$action}.");
        $this->dispatch('refreshSaldoProductList');
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

    public function render()
    {
        return view('livewire.saldo.saldo-product-form', [
            'units' => $this->units,
            'availableProviders' => $this->availableProviders,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
