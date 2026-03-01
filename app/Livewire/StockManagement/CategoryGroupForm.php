<?php

namespace App\Livewire\StockManagement;

use App\Models\CategoryGroup;
use App\Models\COA;
use App\Models\StockCategory;
use App\Services\BusinessUnitService;
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
    public $coa_inventory_key = '';
    public $coa_revenue_key = '';
    public $coa_expense_key = '';
    public $is_active = true;

    protected $listeners = ['openCategoryGroupModal', 'editCategoryGroup'];

    public function openCategoryGroupModal()
    {
        $this->resetForm();
        $this->business_unit_id = BusinessUnitService::getDefaultBusinessUnitId();
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
        $this->coa_inventory_key = $group->coa_inventory_key ?? '';
        $this->coa_revenue_key = $group->coa_revenue_key ?? '';
        $this->coa_expense_key = $group->coa_expense_key ?? '';
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
        $this->coa_inventory_key = '';
        $this->coa_revenue_key = '';
        $this->coa_expense_key = '';
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
            'coa_inventory_key' => 'nullable|string|max:50',
            'coa_revenue_key' => 'nullable|string|max:50',
            'coa_expense_key' => 'nullable|string|max:50',
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
        $this->business_unit_id = BusinessUnitService::resolveBusinessUnitId($this->business_unit_id);
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
            'coa_inventory_key' => $this->coa_inventory_key ?: null,
            'coa_revenue_key' => $this->coa_revenue_key ?: null,
            'coa_expense_key' => $this->coa_expense_key ?: null,
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
        return BusinessUnitService::getAvailableUnits();
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
        if (!$this->business_unit_id) return collect();
        return COA::where('is_active', true)
            ->where('is_leaf_account', true)
            ->where('business_unit_id', $this->business_unit_id)
            ->where('type', 'aktiva')
            ->orderBy('code')
            ->get();
    }

    public function getRevenueCoasProperty()
    {
        if (!$this->business_unit_id) return collect();
        return COA::where('is_active', true)
            ->where('is_leaf_account', true)
            ->where('business_unit_id', $this->business_unit_id)
            ->where('type', 'pendapatan')
            ->orderBy('code')
            ->get();
    }

    public function getExpenseCoasProperty()
    {
        if (!$this->business_unit_id) return collect();
        return COA::where('is_active', true)
            ->where('is_leaf_account', true)
            ->where('business_unit_id', $this->business_unit_id)
            ->where('type', 'beban')
            ->orderBy('code')
            ->get();
    }

    public function updatedBusinessUnitId()
    {
        $this->stock_category_id = '';
        $this->coa_inventory_id = '';
        $this->coa_revenue_id = '';
        $this->coa_expense_id = '';
    }

    /**
     * When stock category changes, auto-fill COA keys from parent category.
     */
    public function updatedStockCategoryId($value)
    {
        if ($value) {
            $category = StockCategory::find($value);
            if ($category) {
                // Auto-fill key-based mapping from parent (if not already set)
                if (!$this->coa_inventory_key && $category->coa_inventory_key) {
                    $this->coa_inventory_key = $category->coa_inventory_key;
                }
                if (!$this->coa_expense_key && $category->coa_hpp_key) {
                    $this->coa_expense_key = $category->coa_hpp_key;
                }
                if (!$this->coa_revenue_key && $category->coa_revenue_key) {
                    $this->coa_revenue_key = $category->coa_revenue_key;
                }
            }
        }
    }

    public function render()
    {
        // Flatten account keys for select options, grouped by type
        $defs = \App\Models\BusinessUnitCoaMapping::getAccountKeyDefinitions();
        $inventoryKeys = collect($defs['aktiva'] ?? [])->filter(fn($d) => str_contains($d['key'], 'persediaan'));
        $revenueKeys = collect($defs['pendapatan'] ?? []);
        $expenseKeys = collect($defs['beban'] ?? []);

        return view('livewire.stock-management.category-group-form', [
            'units' => $this->units,
            'categories' => $this->categories,
            'inventoryCoas' => $this->inventoryCoas,
            'revenueCoas' => $this->revenueCoas,
            'expenseCoas' => $this->expenseCoas,
            'inventoryKeys' => $inventoryKeys,
            'revenueKeys' => $revenueKeys,
            'expenseKeys' => $expenseKeys,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
