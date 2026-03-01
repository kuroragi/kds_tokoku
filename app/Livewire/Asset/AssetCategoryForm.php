<?php

namespace App\Livewire\Asset;

use App\Models\AssetCategory;
use App\Services\BusinessUnitService;
use Illuminate\Validation\Rule;
use Livewire\Component;

class AssetCategoryForm extends Component
{
    public bool $showModal = false;
    public ?int $categoryId = null;
    public bool $isEditing = false;

    public $business_unit_id = '';
    public $code = '';
    public $name = '';
    public $description = '';
    public $useful_life_months = 60;
    public $depreciation_method = 'straight_line';
    public $coa_preset = '';
    public $coa_asset_key = '';
    public $coa_accumulated_dep_key = '';
    public $coa_expense_dep_key = '';
    public $is_active = true;

    protected $listeners = ['openAssetCategoryModal', 'editAssetCategory'];

    public function openAssetCategoryModal()
    {
        $this->resetForm();
        $this->business_unit_id = BusinessUnitService::getDefaultBusinessUnitId();
        $this->showModal = true;
    }

    public function editAssetCategory($id)
    {
        $category = AssetCategory::findOrFail($id);
        $this->categoryId = $category->id;
        $this->isEditing = true;
        $this->business_unit_id = $category->business_unit_id;
        $this->code = $category->code;
        $this->name = $category->name;
        $this->description = $category->description ?? '';
        $this->useful_life_months = $category->useful_life_months;
        $this->depreciation_method = $category->depreciation_method;
        $this->coa_asset_key = $category->coa_asset_key ?? '';
        $this->coa_accumulated_dep_key = $category->coa_accumulated_dep_key ?? '';
        $this->coa_expense_dep_key = $category->coa_expense_dep_key ?? '';
        $this->coa_preset = '';
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
        $this->description = '';
        $this->useful_life_months = 60;
        $this->depreciation_method = 'straight_line';
        $this->coa_preset = '';
        $this->coa_asset_key = '';
        $this->coa_accumulated_dep_key = '';
        $this->coa_expense_dep_key = '';
        $this->is_active = true;
        $this->resetValidation();
    }

    public function updatedCoaPreset($value)
    {
        $presets = AssetCategory::COA_KEY_PRESETS;
        if (isset($presets[$value])) {
            $this->coa_asset_key = $presets[$value]['coa_asset_key'] ?? '';
            $this->coa_accumulated_dep_key = $presets[$value]['coa_accumulated_dep_key'] ?? '';
            $this->coa_expense_dep_key = $presets[$value]['coa_expense_dep_key'] ?? '';
        }
    }

    protected function rules(): array
    {
        return [
            'business_unit_id' => 'required|exists:business_units,id',
            'code' => [
                'required', 'string', 'max:30',
                Rule::unique('asset_categories', 'code')
                    ->where('business_unit_id', $this->business_unit_id)
                    ->ignore($this->categoryId),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'useful_life_months' => 'required|integer|min:1|max:600',
            'depreciation_method' => 'required|in:straight_line,declining_balance',
            'coa_asset_key' => 'nullable|string|max:50',
            'coa_accumulated_dep_key' => 'nullable|string|max:50',
            'coa_expense_dep_key' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ];
    }

    public function save()
    {
        $unitId = BusinessUnitService::resolveBusinessUnitId($this->business_unit_id);
        $this->business_unit_id = $unitId;
        $this->validate();

        $data = [
            'business_unit_id' => $unitId,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description ?: null,
            'useful_life_months' => $this->useful_life_months,
            'depreciation_method' => $this->depreciation_method,
            'coa_asset_key' => $this->coa_asset_key ?: null,
            'coa_accumulated_dep_key' => $this->coa_accumulated_dep_key ?: null,
            'coa_expense_dep_key' => $this->coa_expense_dep_key ?: null,
            'is_active' => $this->is_active,
        ];

        if ($this->isEditing) {
            AssetCategory::findOrFail($this->categoryId)->update($data);
        } else {
            AssetCategory::create($data);
        }

        $action = $this->isEditing ? 'diperbarui' : 'dibuat';
        $this->dispatch('alert', type: 'success', message: "Kategori '{$this->name}' berhasil {$action}.");
        $this->dispatch('refreshAssetCategoryList');
        $this->closeModal();
    }

    public function getUnitsProperty()
    {
        return BusinessUnitService::getAvailableUnits();
    }

    public function render()
    {
        return view('livewire.asset.asset-category-form', [
            'units' => $this->units,
            'methods' => AssetCategory::DEPRECIATION_METHODS,
            'coaPresets' => AssetCategory::COA_KEY_PRESETS,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
