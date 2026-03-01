<?php

namespace App\Livewire\Closing;

use App\Models\Period;
use App\Services\BusinessUnitService;
use Livewire\Component;

class PeriodList extends Component
{
    public $search = '';
    public $filterUnit = '';
    public $filterYear = '';
    public $filterStatus = '';
    public $sortField = 'start_date';
    public $sortDirection = 'desc';

    protected $listeners = ['refreshPeriodList' => '$refresh'];

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

    public function getAvailableYearsProperty()
    {
        return Period::selectRaw('DISTINCT year')
            ->orderBy('year', 'desc')
            ->pluck('year');
    }

    public function deletePeriod($id)
    {
        $period = Period::findOrFail($id);

        if ($period->is_closed) {
            $this->dispatch('alert', type: 'error', message: "Periode '{$period->name}' sudah ditutup dan tidak bisa dihapus.");
            return;
        }

        if ($period->journalMasters()->count() > 0) {
            $this->dispatch('alert', type: 'error', message: "Periode '{$period->name}' tidak bisa dihapus karena masih memiliki jurnal.");
            return;
        }

        $name = $period->name;
        $period->delete();
        $this->dispatch('alert', type: 'success', message: "Periode '{$name}' berhasil dihapus.");
    }

    public function toggleStatus($id)
    {
        $period = Period::findOrFail($id);

        if ($period->is_closed) {
            $this->dispatch('alert', type: 'error', message: "Periode yang sudah ditutup tidak bisa diubah statusnya.");
            return;
        }

        $period->update(['is_active' => !$period->is_active]);
        $status = $period->is_active ? 'diaktifkan' : 'dinonaktifkan';
        $this->dispatch('alert', type: 'success', message: "Periode '{$period->name}' berhasil {$status}.");
    }

    public function render()
    {
        $query = Period::with('businessUnit');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('code', 'like', "%{$this->search}%")
                    ->orWhere('name', 'like', "%{$this->search}%");
            });
        }

        BusinessUnitService::applyBusinessUnitFilter($query, $this->filterUnit);

        if ($this->filterYear) {
            $query->byYear($this->filterYear);
        }

        if ($this->filterStatus === 'active') {
            $query->active()->open();
        } elseif ($this->filterStatus === 'closed') {
            $query->closed();
        } elseif ($this->filterStatus === 'inactive') {
            $query->where('is_active', false);
        }

        $periods = $query->orderBy($this->sortField, $this->sortDirection)->get();

        return view('livewire.closing.period-list', [
            'periods' => $periods,
            'units' => $this->units,
            'availableYears' => $this->availableYears,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
