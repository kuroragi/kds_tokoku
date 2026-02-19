<?php

namespace App\Livewire\StockManagement;

use App\Models\StockOpname;
use App\Services\BusinessUnitService;
use App\Services\StockOpnameService;
use Livewire\Component;

class StockOpnameList extends Component
{
    public $search = '';
    public $filterUnit = '';
    public $filterStatus = '';
    public $sortField = 'opname_date';
    public $sortDirection = 'desc';

    protected $listeners = ['refreshStockOpnameList' => '$refresh'];

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'desc';
        }
    }

    public function getOpnamesProperty()
    {
        $query = StockOpname::with(['businessUnit', 'details']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('opname_number', 'like', "%{$this->search}%")
                  ->orWhere('pic_name', 'like', "%{$this->search}%")
                  ->orWhere('notes', 'like', "%{$this->search}%");
            });
        }

        BusinessUnitService::applyBusinessUnitFilter($query, $this->filterUnit);

        if ($this->filterStatus !== '') {
            $query->where('status', $this->filterStatus);
        }

        return $query->orderBy($this->sortField, $this->sortDirection)->get();
    }

    public function getUnitsProperty()
    {
        return BusinessUnitService::getAvailableUnits();
    }

    public function approveOpname($id)
    {
        $opname = StockOpname::findOrFail($id);
        $service = new StockOpnameService();
        $service->approveStockOpname($opname);
        $this->dispatch('alert', type: 'success', message: 'Stock opname berhasil disetujui dan stok diperbarui.');
    }

    public function cancelOpname($id)
    {
        $opname = StockOpname::findOrFail($id);
        $service = new StockOpnameService();
        $service->cancelStockOpname($opname);
        $this->dispatch('alert', type: 'success', message: 'Stock opname berhasil dibatalkan.');
    }

    public function deleteOpname($id)
    {
        $opname = StockOpname::findOrFail($id);
        $service = new StockOpnameService();
        $service->deleteStockOpname($opname);
        $this->dispatch('alert', type: 'success', message: 'Stock opname berhasil dihapus.');
    }

    public function render()
    {
        return view('livewire.stock-management.stock-opname-list', [
            'opnames' => $this->opnames,
            'units' => $this->units,
            'statuses' => StockOpname::STATUSES,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
