<?php

namespace App\Livewire\Payroll;

use App\Models\PayrollPeriod;
use App\Services\BusinessUnitService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class PayrollPeriodList extends Component
{
    public $search = '';
    public $filterUnit = '';
    public $filterStatus = '';
    public $filterYear = '';
    public $sortField = 'year';
    public $sortDirection = 'desc';

    protected $listeners = ['refreshPayrollPeriodList' => '$refresh'];

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'desc';
        }
    }

    public function getUnitsProperty()
    {
        return BusinessUnitService::getAvailableUnits();
    }

    public function deletePeriod($id)
    {
        $period = PayrollPeriod::findOrFail($id);
        if ($period->isPaid()) {
            $this->dispatch('alert', type: 'error', message: 'Payroll yang sudah dibayar tidak dapat dihapus.');
            return;
        }

        DB::beginTransaction();
        try {
            $name = $period->name;
            $period->entries()->each(function ($entry) {
                $entry->details()->delete();
                $entry->forceDelete();
            });
            $period->forceDelete();
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->dispatch('alert', type: 'error', message: "Gagal menghapus payroll: {$e->getMessage()}");
            return;
        }

        $this->dispatch('alert', type: 'success', message: "Payroll '{$name}' berhasil dihapus.");
    }

    public function render()
    {
        $query = PayrollPeriod::with('businessUnit');

        if ($this->search) {
            $query->where('name', 'like', "%{$this->search}%");
        }

        BusinessUnitService::applyBusinessUnitFilter($query, $this->filterUnit);

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        if ($this->filterYear) {
            $query->where('year', $this->filterYear);
        }

        $query->orderBy($this->sortField, $this->sortDirection)
            ->orderBy('month', 'desc');

        return view('livewire.payroll.payroll-period-list', [
            'periods' => $query->get(),
            'units' => $this->units,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
            'years' => range(date('Y'), date('Y') - 2),
        ]);
    }
}
