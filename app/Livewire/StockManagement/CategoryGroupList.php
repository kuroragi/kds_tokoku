<?php

namespace App\Livewire\StockManagement;

use App\Models\BusinessUnit;
use App\Models\CategoryGroup;
use App\Models\COA;
use App\Models\StockCategory;
use Livewire\Component;

class CategoryGroupList extends Component
{
    public $search = '';
    public $filterUnit = '';
    public $filterCategory = '';
    public $filterStatus = '';
    public $sortField = 'code';
    public $sortDirection = 'asc';

    protected $listeners = ['refreshCategoryGroupList' => '$refresh'];

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function getGroupsProperty()
    {
        $query = CategoryGroup::with(['businessUnit', 'stockCategory', 'coaInventory', 'coaRevenue', 'coaExpense'])
            ->withCount('stocks');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('code', 'like', "%{$this->search}%")
                  ->orWhere('name', 'like', "%{$this->search}%");
            });
        }

        if ($this->filterUnit) {
            $query->where('business_unit_id', $this->filterUnit);
        }

        if ($this->filterCategory) {
            $query->where('stock_category_id', $this->filterCategory);
        }

        if ($this->filterStatus !== '') {
            $query->where('is_active', $this->filterStatus);
        }

        return $query->orderBy($this->sortField, $this->sortDirection)->get();
    }

    public function getUnitsProperty()
    {
        return BusinessUnit::active()->orderBy('name')->get();
    }

    public function getCategoriesProperty()
    {
        $query = StockCategory::active();
        if ($this->filterUnit) {
            $query->where('business_unit_id', $this->filterUnit);
        }
        return $query->orderBy('name')->get();
    }

    public function deleteGroup($id)
    {
        $group = CategoryGroup::findOrFail($id);

        if ($group->stocks()->count() > 0) {
            $this->dispatch('alert', type: 'error', message: "Tidak dapat menghapus grup '{$group->name}' karena masih memiliki stok terkait.");
            return;
        }

        $name = $group->name;
        $group->delete();

        $this->dispatch('alert', type: 'success', message: "Grup kategori '{$name}' berhasil dihapus.");
    }

    public function toggleStatus($id)
    {
        $group = CategoryGroup::findOrFail($id);
        $group->is_active = !$group->is_active;
        $group->save();

        $status = $group->is_active ? 'diaktifkan' : 'dinonaktifkan';
        $this->dispatch('alert', type: 'success', message: "Grup kategori '{$group->name}' berhasil {$status}.");
    }

    public function render()
    {
        return view('livewire.stock-management.category-group-list', [
            'groups' => $this->groups,
            'units' => $this->units,
            'categories' => $this->categories,
        ]);
    }
}
