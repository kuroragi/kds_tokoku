<?php

namespace App\Livewire\StockManagement;

use App\Models\CategoryGroup;
use App\Models\Stock;
use App\Services\BusinessUnitService;
use Livewire\Component;

class StockList extends Component
{
    public string $search = '';
    public string $filterUnit = '';
    public string $filterCategory = '';
    public string $filterStatus = '';
    public string $sortField = 'code';
    public string $sortDirection = 'asc';

    protected $listeners = ['refreshStockList' => '$refresh'];

    public function updatedSearch()
    {
    }

    public function updatedFilterUnit()
    {
        $this->filterCategory = '';
    }

    public function updatedFilterCategory()
    {
    }

    public function updatedFilterStatus()
    {
    }

    public function sortBy($column)
    {
        if ($this->sortField === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function deleteStock($id)
    {
        $stock = Stock::findOrFail($id);
        $stock->delete();
        $this->dispatch('alert', type: 'success', message: "Stok '{$stock->name}' berhasil dihapus.");
    }

    public function toggleStatus($id)
    {
        $stock = Stock::findOrFail($id);
        $stock->update(['is_active' => !$stock->is_active]);

        $status = $stock->is_active ? 'diaktifkan' : 'dinonaktifkan';
        $this->dispatch('alert', type: 'success', message: "Stok '{$stock->name}' berhasil {$status}.");
    }

    public function getUnitsProperty()
    {
        return BusinessUnitService::getAvailableUnits();
    }

    public function getCategoryGroupsProperty()
    {
        if (!BusinessUnitService::isSuperAdmin()) {
            $unitId = BusinessUnitService::getUserBusinessUnitId();
            if ($unitId) {
                return CategoryGroup::active()
                    ->where('business_unit_id', $unitId)
                    ->orderBy('name')
                    ->get();
            }
            return collect();
        }

        if (!$this->filterUnit) {
            return CategoryGroup::active()->orderBy('name')->get();
        }
        return CategoryGroup::active()
            ->where('business_unit_id', $this->filterUnit)
            ->orderBy('name')
            ->get();
    }

    public function render()
    {
        $query = Stock::with(['businessUnit', 'categoryGroup.stockCategory', 'unitOfMeasure']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('code', 'like', "%{$this->search}%")
                    ->orWhere('name', 'like', "%{$this->search}%")
                    ->orWhere('barcode', 'like', "%{$this->search}%");
            });
        }

        BusinessUnitService::applyBusinessUnitFilter($query, $this->filterUnit);

        if ($this->filterCategory) {
            $query->where('category_group_id', $this->filterCategory);
        }

        if ($this->filterStatus !== '') {
            $query->where('is_active', $this->filterStatus);
        }

        $query->orderBy($this->sortField, $this->sortDirection);

        return view('livewire.stock-management.stock-list', [
            'stocks' => $query->get(),
            'units' => $this->units,
            'categoryGroups' => $this->categoryGroups,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
