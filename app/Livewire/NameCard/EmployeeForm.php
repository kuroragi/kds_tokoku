<?php

namespace App\Livewire\NameCard;

use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use App\Services\BusinessUnitService;
use Illuminate\Validation\Rule;
use Livewire\Component;

class EmployeeForm extends Component
{
    public bool $showModal = false;
    public ?int $employeeId = null;
    public bool $isEditing = false;
    public string $activeTab = 'umum';

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

    // Salary & bank fields
    public $user_id = '';
    public $base_salary = '';
    public $bank_name = '';
    public $bank_account_number = '';
    public $bank_account_name = '';
    public $npwp = '';
    public $ptkp_status = '';

    protected $listeners = ['openEmployeeModal', 'editEmployee'];

    public function openEmployeeModal()
    {
        $this->resetForm();
        $this->business_unit_id = BusinessUnitService::getDefaultBusinessUnitId();
        $this->activeTab = 'umum';
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

        // Salary & bank fields
        $this->user_id = $employee->user_id ?? '';
        $this->base_salary = $employee->base_salary ?? '';
        $this->bank_name = $employee->bank_name ?? '';
        $this->bank_account_number = $employee->bank_account_number ?? '';
        $this->bank_account_name = $employee->bank_account_name ?? '';
        $this->npwp = $employee->npwp ?? '';
        $this->ptkp_status = $employee->ptkp_status ?? '';

        $this->activeTab = 'umum';
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
        $this->user_id = '';
        $this->base_salary = '';
        $this->bank_name = '';
        $this->bank_account_number = '';
        $this->bank_account_name = '';
        $this->npwp = '';
        $this->ptkp_status = '';
        $this->activeTab = 'umum';
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
            'user_id' => 'nullable|exists:users,id',
            'base_salary' => 'nullable|numeric|min:0',
            'bank_name' => 'nullable|string|max:50',
            'bank_account_number' => 'nullable|string|max:30',
            'bank_account_name' => 'nullable|string|max:100',
            'npwp' => 'nullable|string|max:30',
            'ptkp_status' => ['nullable', Rule::in(array_keys(Employee::PTKP_STATUSES))],
        ];
    }

    protected $messages = [
        'business_unit_id.required' => 'Unit usaha wajib dipilih.',
        'code.required' => 'Kode karyawan wajib diisi.',
        'code.unique' => 'Kode karyawan sudah digunakan pada unit usaha ini.',
        'name.required' => 'Nama karyawan wajib diisi.',
        'base_salary.numeric' => 'Gaji pokok harus berupa angka.',
        'base_salary.min' => 'Gaji pokok tidak boleh negatif.',
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
            'user_id' => $this->user_id ?: null,
            'base_salary' => $this->base_salary !== '' ? (int) $this->base_salary : null,
            'bank_name' => $this->bank_name ?: null,
            'bank_account_number' => $this->bank_account_number ?: null,
            'bank_account_name' => $this->bank_account_name ?: null,
            'npwp' => $this->npwp ?: null,
            'ptkp_status' => $this->ptkp_status ?: null,
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

    public function getUsersProperty()
    {
        if (!$this->business_unit_id) {
            return collect();
        }
        return User::where('business_unit_id', $this->business_unit_id)
            ->where('is_active', true)
            ->whereDoesntHave('employee', function ($q) {
                if ($this->employeeId) {
                    $q->where('employees.id', '!=', $this->employeeId);
                }
            })
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
