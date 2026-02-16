<?php

namespace App\Livewire\StockManagement;

use App\Models\BusinessUnit;
use App\Models\CategoryGroup;
use App\Models\Stock;
use App\Models\UnitOfMeasure;
use Illuminate\Validation\Rule;
use Livewire\Component;

class StockForm extends Component
{
    public bool $showModal = false;
    public ?int $stockId = null;
    public bool $isEditing = false;

    // Fields
    public $business_unit_id = '';
    public $category_group_id = '';
    public $unit_of_measure_id = '';
    public $code = '';
    public $name = '';
    public $barcode = '';
    public $description = '';
    public $buy_price = 0;
    public $sell_price = 0;
    public $min_stock = 0;
    public $is_active = true;

    protected $listeners = ['openStockModal', 'editStock'];

    public function openStockModal()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function editStock($id)
    {
        $stock = Stock::findOrFail($id);

        $this->stockId = $stock->id;
        $this->isEditing = true;
        $this->business_unit_id = $stock->business_unit_id;
        $this->category_group_id = $stock->category_group_id;
        $this->unit_of_measure_id = $stock->unit_of_measure_id;
        $this->code = $stock->code;
        $this->name = $stock->name;
        $this->barcode = $stock->barcode ?? '';
        $this->description = $stock->description ?? '';
        $this->buy_price = $stock->buy_price;
        $this->sell_price = $stock->sell_price;
        $this->min_stock = $stock->min_stock;
        $this->is_active = $stock->is_active;
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->stockId = null;
        $this->isEditing = false;
        $this->business_unit_id = '';
        $this->category_group_id = '';
        $this->unit_of_measure_id = '';
        $this->code = '';
        $this->name = '';
        $this->barcode = '';
        $this->description = '';
        $this->buy_price = 0;
        $this->sell_price = 0;
        $this->min_stock = 0;
        $this->is_active = true;
        $this->resetValidation();
    }

    protected function rules(): array
    {
        return [
            'business_unit_id' => 'required|exists:business_units,id',
            'category_group_id' => 'required|exists:category_groups,id',
            'unit_of_measure_id' => 'required|exists:unit_of_measures,id',
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('stocks', 'code')
                    ->where('business_unit_id', $this->business_unit_id)
                    ->ignore($this->stockId),
            ],
            'name' => 'required|string|max:255',
            'barcode' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:1000',
            'buy_price' => 'required|numeric|min:0',
            'sell_price' => 'required|numeric|min:0',
            'min_stock' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ];
    }

    protected $messages = [
        'business_unit_id.required' => 'Unit usaha wajib dipilih.',
        'category_group_id.required' => 'Grup kategori wajib dipilih.',
        'unit_of_measure_id.required' => 'Satuan wajib dipilih.',
        'code.required' => 'Kode stok wajib diisi.',
        'code.unique' => 'Kode stok sudah digunakan pada unit usaha ini.',
        'name.required' => 'Nama stok wajib diisi.',
        'buy_price.required' => 'Harga beli wajib diisi.',
        'sell_price.required' => 'Harga jual wajib diisi.',
        'min_stock.required' => 'Stok minimal wajib diisi.',
    ];

    public function save()
    {
        $this->validate();

        $data = [
            'business_unit_id' => $this->business_unit_id,
            'category_group_id' => $this->category_group_id,
            'unit_of_measure_id' => $this->unit_of_measure_id,
            'code' => $this->code,
            'name' => $this->name,
            'barcode' => $this->barcode ?: null,
            'description' => $this->description ?: null,
            'buy_price' => $this->buy_price,
            'sell_price' => $this->sell_price,
            'min_stock' => $this->min_stock,
            'is_active' => $this->is_active,
        ];

        if ($this->isEditing) {
            $stock = Stock::findOrFail($this->stockId);
            $stock->update($data);
        } else {
            $data['current_stock'] = 0;
            Stock::create($data);
        }

        $action = $this->isEditing ? 'diperbarui' : 'dibuat';
        $this->dispatch('alert', type: 'success', message: "Stok '{$this->name}' berhasil {$action}.");
        $this->dispatch('refreshStockList');
        $this->closeModal();
    }

    public function getUnitsProperty()
    {
        return BusinessUnit::active()->orderBy('name')->get();
    }

    public function getCategoryGroupsProperty()
    {
        if (!$this->business_unit_id) {
            return collect();
        }
        return CategoryGroup::active()
            ->where('business_unit_id', $this->business_unit_id)
            ->orderBy('name')
            ->get();
    }

    public function getMeasuresProperty()
    {
        if (!$this->business_unit_id) {
            return collect();
        }
        return UnitOfMeasure::active()
            ->where('business_unit_id', $this->business_unit_id)
            ->orderBy('name')
            ->get();
    }

    public function updatedBusinessUnitId()
    {
        $this->category_group_id = '';
        $this->unit_of_measure_id = '';
    }

    public function render()
    {
        return view('livewire.stock-management.stock-form', [
            'units' => $this->units,
            'categoryGroups' => $this->categoryGroups,
            'measures' => $this->measures,
        ]);
    }
}
