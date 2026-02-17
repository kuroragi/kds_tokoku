<?php

namespace App\Livewire\Loan;

use App\Models\EmployeeLoan;
use App\Services\BusinessUnitService;
use Livewire\Component;

class EmployeeLoanList extends Component
{
    public $search = '';
    public $filterUnit = '';
    public $filterStatus = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';

    protected $listeners = ['refreshEmployeeLoanList' => '$refresh'];

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

    public function voidLoan($id)
    {
        try {
            $loan = EmployeeLoan::findOrFail($id);
            $service = app(\App\Services\EmployeeLoanService::class);
            $service->voidLoan($loan);
            $this->dispatch('alert', type: 'success', message: "Pinjaman '{$loan->loan_number}' berhasil dibatalkan.");
        } catch (\Exception $e) {
            $this->dispatch('alert', type: 'error', message: $e->getMessage());
        }
    }

    public function render()
    {
        $query = EmployeeLoan::with(['employee', 'businessUnit']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('loan_number', 'like', "%{$this->search}%")
                    ->orWhereHas('employee', function ($q2) {
                        $q2->where('name', 'like', "%{$this->search}%");
                    });
            });
        }

        BusinessUnitService::applyBusinessUnitFilter($query, $this->filterUnit);

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        $query->orderBy($this->sortField, $this->sortDirection);

        return view('livewire.loan.employee-loan-list', [
            'loans' => $query->get(),
            'units' => $this->units,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
            'statuses' => EmployeeLoan::STATUSES,
        ]);
    }
}
