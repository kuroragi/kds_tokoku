<?php

namespace App\Livewire\NameCard;

use App\Models\Employee;
use App\Models\EmployeeSalaryComponent;
use App\Models\SalaryComponent;
use Livewire\Component;

class EmployeeSalaryAssignment extends Component
{
    public bool $showModal = false;
    public ?int $employeeId = null;
    public string $employeeName = '';
    public array $assignments = [];
    public $availableComponents = [];

    public $newComponentId = '';
    public $newAmount = '';

    protected $listeners = ['openEmployeeSalaryAssignment'];

    public function openEmployeeSalaryAssignment($id)
    {
        $employee = Employee::findOrFail($id);
        $this->employeeId = $employee->id;
        $this->employeeName = $employee->name;

        $this->loadAssignments();
        $this->loadAvailableComponents($employee->business_unit_id);
        $this->showModal = true;
    }

    public function loadAssignments()
    {
        $this->assignments = EmployeeSalaryComponent::where('employee_id', $this->employeeId)
            ->with('salaryComponent')
            ->get()
            ->map(fn($esc) => [
                'id' => $esc->id,
                'salary_component_id' => $esc->salary_component_id,
                'component_name' => $esc->salaryComponent->name ?? '-',
                'component_code' => $esc->salaryComponent->code ?? '-',
                'amount' => $esc->amount,
            ])
            ->toArray();
    }

    public function loadAvailableComponents($businessUnitId)
    {
        $existingIds = collect($this->assignments)->pluck('salary_component_id')->toArray();

        $this->availableComponents = SalaryComponent::byBusinessUnit($businessUnitId)
            ->active()
            ->template()
            ->whereNotIn('id', $existingIds)
            ->orderBy('sort_order')
            ->get()
            ->toArray();
    }

    public function addComponent()
    {
        if (!$this->newComponentId || $this->newAmount === '') {
            return;
        }

        EmployeeSalaryComponent::create([
            'employee_id' => $this->employeeId,
            'salary_component_id' => $this->newComponentId,
            'amount' => (int) $this->newAmount,
        ]);

        $this->newComponentId = '';
        $this->newAmount = '';
        $this->loadAssignments();

        $employee = Employee::find($this->employeeId);
        if ($employee) {
            $this->loadAvailableComponents($employee->business_unit_id);
        }

        $this->dispatch('alert', type: 'success', message: 'Komponen gaji berhasil ditambahkan.');
    }

    public function copyFromPosition()
    {
        $employee = Employee::findOrFail($this->employeeId);
        if (!$employee->position_id) {
            $this->dispatch('alert', type: 'error', message: 'Karyawan belum memiliki jabatan.');
            return;
        }

        $positionComponents = \App\Models\PositionSalaryComponent::where('position_id', $employee->position_id)
            ->get();

        foreach ($positionComponents as $pc) {
            EmployeeSalaryComponent::updateOrCreate(
                [
                    'employee_id' => $this->employeeId,
                    'salary_component_id' => $pc->salary_component_id,
                ],
                ['amount' => $pc->amount]
            );
        }

        $this->loadAssignments();
        $this->loadAvailableComponents($employee->business_unit_id);
        $this->dispatch('alert', type: 'success', message: 'Komponen gaji berhasil disalin dari template jabatan.');
    }

    public function updateAmount($assignmentId, $amount)
    {
        EmployeeSalaryComponent::where('id', $assignmentId)->update([
            'amount' => (int) $amount,
        ]);
        $this->dispatch('alert', type: 'success', message: 'Nominal berhasil diperbarui.');
    }

    public function removeComponent($assignmentId)
    {
        EmployeeSalaryComponent::where('id', $assignmentId)->delete();
        $this->loadAssignments();

        $employee = Employee::find($this->employeeId);
        if ($employee) {
            $this->loadAvailableComponents($employee->business_unit_id);
        }

        $this->dispatch('alert', type: 'success', message: 'Komponen gaji berhasil dihapus.');
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->employeeId = null;
        $this->employeeName = '';
        $this->assignments = [];
        $this->availableComponents = [];
        $this->newComponentId = '';
        $this->newAmount = '';
    }

    public function render()
    {
        return view('livewire.name-card.employee-salary-assignment');
    }
}
