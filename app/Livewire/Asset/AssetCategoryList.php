<?php

namespace App\Livewire\Asset;

use App\Models\AssetCategory;
use App\Services\BusinessUnitService;
use Livewire\Component;

class AssetCategoryList extends Component
{
    public $search = '';
    public $filterUnit = '';
    public $filterStatus = '';
    public $sortField = 'code';
    public $sortDirection = 'asc';

    protected $listeners = ['refreshAssetCategoryList' => '$refresh'];

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function getUnitsProperty()
    {
        return BusinessUnitService::getAvailableUnits();
    }

    public function deleteCategory($id)
    {
        $category = AssetCategory::findOrFail($id);

        if ($category->assets()->count() > 0) {
            $this->dispatch('alert', type: 'error', message: "Kategori '{$category->name}' tidak bisa dihapus karena masih memiliki aset.");
            return;
        }

        $name = $category->name;
        $category->delete();
        $this->dispatch('alert', type: 'success', message: "Kategori '{$name}' berhasil dihapus.");
    }

    public function toggleStatus($id)
    {
        $category = AssetCategory::findOrFail($id);
        $category->update(['is_active' => !$category->is_active]);
        $status = $category->is_active ? 'diaktifkan' : 'dinonaktifkan';
        $this->dispatch('alert', type: 'success', message: "Kategori '{$category->name}' berhasil {$status}.");
    }

    public function render()
    {
        $query = AssetCategory::with('businessUnit');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('code', 'like', "%{$this->search}%")
                    ->orWhere('name', 'like', "%{$this->search}%");
            });
        }

        if (!BusinessUnitService::isSuperAdmin()) {
            $unitId = BusinessUnitService::getUserBusinessUnitId();
            if ($unitId) $query->byBusinessUnit($unitId);
        } elseif ($this->filterUnit) {
            $query->byBusinessUnit($this->filterUnit);
        }

        if ($this->filterStatus !== '') {
            $query->where('is_active', $this->filterStatus);
        }

        $categories = $query->orderBy($this->sortField, $this->sortDirection)->get();

        return view('livewire.asset.asset-category-list', [
            'categories' => $categories,
            'units' => $this->units,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
