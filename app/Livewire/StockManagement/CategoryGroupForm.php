<?php

namespace App\Livewire\StockManagement;

use App\Models\BusinessUnit;
use App\Models\CategoryGroup;
use App\Models\COA;
use App\Models\StockCategory;
use Illuminate\Validation\Rule;
use Livewire\Component;

class CategoryGroupForm extends Component
{
    public bool $showModal = false;
    public ?int $groupId = null;
    public bool $isEditing = false;

    // Fields
    public $business_unit_id = '';
    public $stock_category_id = '';
    public $code = '';
    public $name = '';
    public $description = '';
    public $coa_inventory_id = '';
    public $coa_revenue_id = '';
    public $coa_expense_id = '';
    public $is_active = true;

    protected $listeners = ['openCategoryGroupModal', 'editCategoryGroup'];

    public function openCategoryGroupModal()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function editCategoryGroup($id)
    {
        $group = CategoryGroup::findOrFail($id);

        $this->groupId = $group->id;
        $this->isEditing = true;
        $this->business_unit_id = $group->business_unit_id;
        $this->stock_category_id = $group->stock_category_id;
        $this->code = $group->code;
        $this->name = $group->name;
        $this->description = $group->description ?? '';
        $this->coa_inventory_id = $group->coa_inventory_id ?? '';
        $this->coa_revenue_id = $group->coa_revenue_id ?? '';
        $this->coa_expense_id = $group->coa_expense_id ?? '';
        $this->is_active = $group->is_active;
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->groupId = null;
        $this->isEditing = false;
        $this->business_unit_id = '';
        $this->stock_category_id = '';
        $this->code = '';
        $this->name = '';
        $this->description = '';
        $this->coa_inventory_id = '';
        $this->coa_revenue_id = '';
        $this->coa_expense_id = '';
        $this->is_active = true;
        $this->resetValidation();
    }

    protected function rules(): array
    {
        return [
            'business_unit_id' => 'required|exists:business_units,id',
            'stock_category_id' => 'required|exists:stock_categories,id',
            'code' => [
                'required', 'string', 'max:20',
                Rule::unique('category_groups', 'code')
                    ->where('business_unit_id', $this->business_unit_id)
                    ->ignore($this->groupId),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'coa_inventory_id' => 'nullable|exists:c_o_a_s,id',
            'coa_revenue_id' => 'nullable|exists:c_o_a_s,id',
            'coa_expense_id' => 'nullable|exists:c_o_a_s,id',
            'is_active' => 'boolean',
        ];
    }

    protected $messages = [
        'business_unit_id.required' => 'Unit usaha wajib dipilih.',
        'stock_category_id.required' => 'Kategori stok wajib dipilih.',
        'code.required' => 'Kode grup wajib diisi.',
        'code.unique' => 'Kode grup sudah digunakan pada unit usaha ini.',
        'name.required' => 'Nama grup wajib diisi.',
    ];

    public function save()
    {
        $this->validate();

        $data = [
            'business_unit_id' => $this->business_unit_id,
            'stock_category_id' => $this->stock_category_id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description ?: null,
            'coa_inventory_id' => $this->coa_inventory_id ?: null,
            'coa_revenue_id' => $this->coa_revenue_id ?: null,
            'coa_expense_id' => $this->coa_expense_id ?: null,
            'is_active' => $this->is_active,
        ];

        if ($this->isEditing) {
            $group = CategoryGroup::findOrFail($this->groupId);
            $group->update($data);
        } else {
            CategoryGroup::create($data);
        }

        $action = $this->isEditing ? 'diperbarui' : 'dibuat';
        $this->dispatch('alert', type: 'success', message: "Grup kategori '{$this->name}' berhasil {$action}.");
        $this->dispatch('refreshCategoryGroupList');
        $this->closeModal();
    }

    public function getUnitsProperty()
    {
        return BusinessUnit::active()->orderBy('name')->get();
    }

    public function getCategoriesProperty()
    {
        if (!$this->business_unit_id) {
            return collect();
        }
        return StockCategory::active()
            ->where('business_unit_id', $this->business_unit_id)
            ->orderBy('name')
            ->get();
    }

    public function getInventoryCoasProperty()
    {
        return COA::where('is_active', true)
            ->where('is_leaf_account', true)
            ->where('type', 'aktiva')
            ->orderBy('code')
            ->get();
    }

    public function getRevenueCoasProperty()
    {
        return COA::where('is_active', true)
            ->where('is_leaf_account', true)
            ->where('type', 'pendapatan')
            ->orderBy('code')
            ->get();
    }

    public function getExpenseCoasProperty()
    {
        return COA::where('is_active', true)
            ->where('is_leaf_account', true)
            ->where('type', 'beban')
            ->orderBy('code')
            ->get();
    }

    public function updatedBusinessUnitId()
    {
        $this->stock_category_id = '';
    }

    public function render()
    {
        return view('livewire.stock-management.category-group-form', [
            'units' => $this->units,
            'categories' => $this->categories,
            'inventoryCoas' => $this->inventoryCoas,
            'revenueCoas' => $this->revenueCoas,
            'expenseCoas' => $this->expenseCoas,
        ]);
    }
}
