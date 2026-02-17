<?php

namespace App\Livewire\NameCard;

use App\Models\Employee;
use App\Models\Position;
use App\Services\BusinessUnitService;
use Livewire\Component;

class EmployeeList extends Component
{
    public $search = '';
    public $filterUnit = '';
    public $filterPosition = '';
    public $filterStatus = '';
    public $sortField = 'code';
    public $sortDirection = 'asc';

    protected $listeners = ['refreshEmployeeList' => '$refresh'];

    public function updatedFilterUnit()
    {
        $this->filterPosition = '';
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function getPositionsProperty()
    {
        if (!BusinessUnitService::isSuperAdmin()) {
            $unitId = BusinessUnitService::getUserBusinessUnitId();
            if ($unitId) {
                return Position::active()->where('business_unit_id', $unitId)->orderBy('name')->get();
            }
            return collect();
        }

        if (!$this->filterUnit) {
            return Position::active()->whereNotNull('business_unit_id')->orderBy('name')->get();
        }
        return Position::active()->where('business_unit_id', $this->filterUnit)->orderBy('name')->get();
    }

    public function getUnitsProperty()
    {
        return BusinessUnitService::getAvailableUnits();
    }

    public function deleteEmployee($id)
    {
        $employee = Employee::findOrFail($id);
        $name = $employee->name;
        $employee->delete();
        $this->dispatch('alert', type: 'success', message: "Karyawan '{$name}' berhasil dihapus.");
    }

    public function toggleStatus($id)
    {
        $employee = Employee::findOrFail($id);
        $employee->update(['is_active' => !$employee->is_active]);
        $status = $employee->is_active ? 'diaktifkan' : 'dinonaktifkan';
        $this->dispatch('alert', type: 'success', message: "Karyawan '{$employee->name}' berhasil {$status}.");
    }

    public function render()
    {
        $query = Employee::with(['businessUnit', 'position']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('code', 'like', "%{$this->search}%")
                    ->orWhere('name', 'like', "%{$this->search}%")
                    ->orWhere('nik', 'like', "%{$this->search}%");
            });
        }

        BusinessUnitService::applyBusinessUnitFilter($query, $this->filterUnit);

        if ($this->filterPosition) {
            $query->where('position_id', $this->filterPosition);
        }

        if ($this->filterStatus !== '') {
            $query->where('is_active', $this->filterStatus);
        }

        $query->orderBy($this->sortField, $this->sortDirection);

        return view('livewire.name-card.employee-list', [
            'employees' => $query->get(),
            'units' => $this->units,
            'positions' => $this->positions,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
