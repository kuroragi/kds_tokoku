<?php

namespace App\Livewire\StockManagement;

use App\Models\SaldoOpname;
use App\Services\BusinessUnitService;
use App\Services\StockOpnameService;
use Livewire\Component;
use Livewire\WithPagination;

class SaldoOpnameList extends Component
{
    use WithPagination;

    public $search = '';
    public $filterUnit = '';
    public $filterStatus = '';
    public $sortField = 'opname_date';
    public $sortDirection = 'desc';

    protected $listeners = ['refreshSaldoOpnameList' => '$refresh'];

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
        $query = SaldoOpname::with(['businessUnit', 'details.saldoProvider']);

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

        return $query->orderBy($this->sortField, $this->sortDirection)->paginate(15);
    }

    public function getUnitsProperty()
    {
        return BusinessUnitService::getAvailableUnits();
    }

    public function approveOpname($id)
    {
        $opname = SaldoOpname::findOrFail($id);
        $service = new StockOpnameService();
        $service->approveSaldoOpname($opname);
        $this->dispatch('alert', type: 'success', message: 'Saldo opname berhasil disetujui dan saldo diperbarui.');
    }

    public function cancelOpname($id)
    {
        $opname = SaldoOpname::findOrFail($id);
        $service = new StockOpnameService();
        $service->cancelSaldoOpname($opname);
        $this->dispatch('alert', type: 'success', message: 'Saldo opname berhasil dibatalkan.');
    }

    public function deleteOpname($id)
    {
        $opname = SaldoOpname::findOrFail($id);
        $service = new StockOpnameService();
        $service->deleteSaldoOpname($opname);
        $this->dispatch('alert', type: 'success', message: 'Saldo opname berhasil dihapus.');
    }

    public function render()
    {
        return view('livewire.stock-management.saldo-opname-list', [
            'opnames' => $this->opnames,
            'units' => $this->units,
            'statuses' => SaldoOpname::STATUSES,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
