<?php

namespace App\Livewire\StockManagement;

use App\Models\StockCategory;
use App\Services\BusinessUnitService;
use Illuminate\Validation\Rule;
use Livewire\Component;

class StockCategoryForm extends Component
{
    public bool $showModal = false;
    public ?int $categoryId = null;
    public bool $isEditing = false;

    // Fields
    public $business_unit_id = '';
    public $code = '';
    public $name = '';
    public $type = 'barang';
    public $coa_preset = '';
    public $coa_inventory_key = '';
    public $coa_hpp_key = '';
    public $coa_revenue_key = '';
    public $description = '';
    public $is_active = true;

    protected $listeners = ['openStockCategoryModal', 'editStockCategory'];

    public function openStockCategoryModal()
    {
        $this->resetForm();
        $this->business_unit_id = BusinessUnitService::getDefaultBusinessUnitId();
        $this->showModal = true;
    }

    public function editStockCategory($id)
    {
        $category = StockCategory::findOrFail($id);

        $this->categoryId = $category->id;
        $this->isEditing = true;
        $this->business_unit_id = $category->business_unit_id;
        $this->code = $category->code;
        $this->name = $category->name;
        $this->type = $category->type;
        $this->coa_inventory_key = $category->coa_inventory_key ?? '';
        $this->coa_hpp_key = $category->coa_hpp_key ?? '';
        $this->coa_revenue_key = $category->coa_revenue_key ?? '';
        $this->coa_preset = '';
        $this->description = $category->description ?? '';
        $this->is_active = $category->is_active;
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->categoryId = null;
        $this->isEditing = false;
        $this->business_unit_id = '';
        $this->code = '';
        $this->name = '';
        $this->type = 'barang';
        $this->coa_preset = '';
        $this->coa_inventory_key = '';
        $this->coa_hpp_key = '';
        $this->coa_revenue_key = '';
        $this->description = '';
        $this->is_active = true;
        $this->resetValidation();
    }

    public function updatedCoaPreset($value)
    {
        $presets = StockCategory::COA_KEY_PRESETS;
        if (isset($presets[$value])) {
            $this->coa_inventory_key = $presets[$value]['coa_inventory_key'] ?? '';
            $this->coa_hpp_key = $presets[$value]['coa_hpp_key'] ?? '';
            $this->coa_revenue_key = $presets[$value]['coa_revenue_key'] ?? '';
        }
    }

    public function updatedType($value)
    {
        // Auto-select matching preset when type changes
        $presets = StockCategory::COA_KEY_PRESETS;
        if (isset($presets[$value])) {
            $this->coa_preset = $value;
            $this->coa_inventory_key = $presets[$value]['coa_inventory_key'] ?? '';
            $this->coa_hpp_key = $presets[$value]['coa_hpp_key'] ?? '';
            $this->coa_revenue_key = $presets[$value]['coa_revenue_key'] ?? '';
        }
    }

    protected function rules(): array
    {
        return [
            'business_unit_id' => 'required|exists:business_units,id',
            'code' => [
                'required', 'string', 'max:20',
                Rule::unique('stock_categories', 'code')
                    ->where('business_unit_id', $this->business_unit_id)
                    ->ignore($this->categoryId),
            ],
            'name' => 'required|string|max:255',
            'type' => 'required|in:barang,jasa,saldo',
            'coa_inventory_key' => 'nullable|string|max:50',
            'coa_hpp_key' => 'nullable|string|max:50',
            'coa_revenue_key' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ];
    }

    protected $messages = [
        'business_unit_id.required' => 'Unit usaha wajib dipilih.',
        'code.required' => 'Kode kategori wajib diisi.',
        'code.unique' => 'Kode kategori sudah digunakan pada unit usaha ini.',
        'name.required' => 'Nama kategori wajib diisi.',
        'type.required' => 'Tipe kategori wajib dipilih.',
    ];

    public function save()
    {
        $this->business_unit_id = BusinessUnitService::resolveBusinessUnitId($this->business_unit_id);
        $this->validate();

        $data = [
            'business_unit_id' => $this->business_unit_id,
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type,
            'coa_inventory_key' => $this->coa_inventory_key ?: null,
            'coa_hpp_key' => $this->coa_hpp_key ?: null,
            'coa_revenue_key' => $this->coa_revenue_key ?: null,
            'description' => $this->description ?: null,
            'is_active' => $this->is_active,
        ];

        if ($this->isEditing) {
            $category = StockCategory::findOrFail($this->categoryId);
            $category->update($data);
        } else {
            StockCategory::create($data);
        }

        $action = $this->isEditing ? 'diperbarui' : 'dibuat';
        $this->dispatch('alert', type: 'success', message: "Kategori stok '{$this->name}' berhasil {$action}.");
        $this->dispatch('refreshStockCategoryList');
        $this->closeModal();
    }

    public function getUnitsProperty()
    {
        return BusinessUnitService::getAvailableUnits();
    }

    public function render()
    {
        return view('livewire.stock-management.stock-category-form', [
            'units' => $this->units,
            'types' => StockCategory::getTypes(),
            'coaPresets' => StockCategory::COA_KEY_PRESETS,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
