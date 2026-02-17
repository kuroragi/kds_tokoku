<?php

namespace App\Livewire\NameCard;

use App\Models\Employee;
use App\Models\Position;
use App\Services\BusinessUnitService;
use Illuminate\Validation\Rule;
use Livewire\Component;

class EmployeeForm extends Component
{
    public bool $showModal = false;
    public ?int $employeeId = null;
    public bool $isEditing = false;

    public $business_unit_id = '';
    public $position_id = '';
    public $code = '';
    public $name = '';
    public $nik = '';
    public $phone = '';
    public $email = '';
    public $address = '';
    public $join_date = '';
    public $is_active = true;

    protected $listeners = ['openEmployeeModal', 'editEmployee'];

    public function openEmployeeModal()
    {
        $this->resetForm();
        $this->business_unit_id = BusinessUnitService::getDefaultBusinessUnitId();
        $this->showModal = true;
    }

    public function editEmployee($id)
    {
        $employee = Employee::findOrFail($id);

        $this->employeeId = $employee->id;
        $this->isEditing = true;
        $this->business_unit_id = $employee->business_unit_id;
        $this->position_id = $employee->position_id ?? '';
        $this->code = $employee->code;
        $this->name = $employee->name;
        $this->nik = $employee->nik ?? '';
        $this->phone = $employee->phone ?? '';
        $this->email = $employee->email ?? '';
        $this->address = $employee->address ?? '';
        $this->join_date = $employee->join_date ? $employee->join_date->format('Y-m-d') : '';
        $this->is_active = $employee->is_active;
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->employeeId = null;
        $this->isEditing = false;
        $this->business_unit_id = '';
        $this->position_id = '';
        $this->code = '';
        $this->name = '';
        $this->nik = '';
        $this->phone = '';
        $this->email = '';
        $this->address = '';
        $this->join_date = '';
        $this->is_active = true;
        $this->resetValidation();
    }

    protected function rules(): array
    {
        return [
            'business_unit_id' => 'required|exists:business_units,id',
            'position_id' => 'nullable|exists:positions,id',
            'code' => [
                'required', 'string', 'max:30',
                Rule::unique('employees', 'code')
                    ->where('business_unit_id', $this->business_unit_id)
                    ->ignore($this->employeeId),
            ],
            'name' => 'required|string|max:255',
            'nik' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:1000',
            'join_date' => 'nullable|date',
            'is_active' => 'boolean',
        ];
    }

    protected $messages = [
        'business_unit_id.required' => 'Unit usaha wajib dipilih.',
        'code.required' => 'Kode karyawan wajib diisi.',
        'code.unique' => 'Kode karyawan sudah digunakan pada unit usaha ini.',
        'name.required' => 'Nama karyawan wajib diisi.',
    ];

    public function save()
    {
        $this->business_unit_id = BusinessUnitService::resolveBusinessUnitId($this->business_unit_id);
        $this->validate();

        $data = [
            'business_unit_id' => $this->business_unit_id,
            'position_id' => $this->position_id ?: null,
            'code' => $this->code,
            'name' => $this->name,
            'nik' => $this->nik ?: null,
            'phone' => $this->phone ?: null,
            'email' => $this->email ?: null,
            'address' => $this->address ?: null,
            'join_date' => $this->join_date ?: null,
            'is_active' => $this->is_active,
        ];

        if ($this->isEditing) {
            $employee = Employee::findOrFail($this->employeeId);
            $employee->update($data);
        } else {
            Employee::create($data);
        }

        $action = $this->isEditing ? 'diperbarui' : 'dibuat';
        $this->dispatch('alert', type: 'success', message: "Karyawan '{$this->name}' berhasil {$action}.");
        $this->dispatch('refreshEmployeeList');
        $this->closeModal();
    }

    public function getUnitsProperty()
    {
        return BusinessUnitService::getAvailableUnits();
    }

    public function getPositionsProperty()
    {
        if (!$this->business_unit_id) {
            return collect();
        }
        return Position::active()
            ->where('business_unit_id', $this->business_unit_id)
            ->orderBy('name')
            ->get();
    }

    public function updatedBusinessUnitId()
    {
        $this->position_id = '';
    }

    public function render()
    {
        return view('livewire.name-card.employee-form', [
            'units' => $this->units,
            'positions' => $this->positions,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
