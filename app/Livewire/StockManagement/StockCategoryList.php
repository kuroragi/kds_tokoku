<?php

namespace App\Livewire\StockManagement;

use App\Models\BusinessUnit;
use App\Models\StockCategory;
use Livewire\Component;

class StockCategoryList extends Component
{
    public $search = '';
    public $filterUnit = '';
    public $filterType = '';
    public $filterStatus = '';
    public $sortField = 'code';
    public $sortDirection = 'asc';

    protected $listeners = ['refreshStockCategoryList' => '$refresh'];

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function getCategoriesProperty()
    {
        $query = StockCategory::with('businessUnit')
            ->withCount('categoryGroups');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('code', 'like', "%{$this->search}%")
                  ->orWhere('name', 'like', "%{$this->search}%");
            });
        }

        if ($this->filterUnit) {
            $query->where('business_unit_id', $this->filterUnit);
        }

        if ($this->filterType) {
            $query->where('type', $this->filterType);
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

    public function deleteCategory($id)
    {
        $category = StockCategory::findOrFail($id);

        if ($category->categoryGroups()->count() > 0) {
            $this->dispatch('alert', type: 'error', message: "Tidak dapat menghapus kategori '{$category->name}' karena masih memiliki grup kategori.");
            return;
        }

        $name = $category->name;
        $category->delete();

        $this->dispatch('alert', type: 'success', message: "Kategori stok '{$name}' berhasil dihapus.");
    }

    public function toggleStatus($id)
    {
        $category = StockCategory::findOrFail($id);
        $category->is_active = !$category->is_active;
        $category->save();

        $status = $category->is_active ? 'diaktifkan' : 'dinonaktifkan';
        $this->dispatch('alert', type: 'success', message: "Kategori stok '{$category->name}' berhasil {$status}.");
    }

    public function render()
    {
        return view('livewire.stock-management.stock-category-list', [
            'categories' => $this->categories,
            'units' => $this->units,
            'types' => StockCategory::getTypes(),
        ]);
    }
}
