<?php

namespace App\Livewire\Payroll;

use App\Models\SalaryComponent;
use App\Services\BusinessUnitService;
use Illuminate\Validation\Rule;
use Livewire\Component;

class SalaryComponentForm extends Component
{
    public bool $showModal = false;
    public ?int $componentId = null;
    public bool $isEditing = false;

    public $business_unit_id = '';
    public $code = '';
    public $name = '';
    public $type = 'earning';
    public $category = 'tunjangan_tetap';
    public $apply_method = 'template';
    public $calculation_type = 'fixed';
    public $employee_field_name = '';
    public $setting_key = '';
    public $percentage_base = 'gaji_pokok';
    public $default_amount = '';
    public $is_taxable = false;
    public $is_active = true;
    public $sort_order = 0;

    protected $listeners = ['openSalaryComponentModal', 'editSalaryComponent'];

    public function openSalaryComponentModal()
    {
        $this->resetForm();
        $this->business_unit_id = BusinessUnitService::getDefaultBusinessUnitId();
        $this->showModal = true;
    }

    public function editSalaryComponent($id)
    {
        $comp = SalaryComponent::findOrFail($id);

        $this->componentId = $comp->id;
        $this->isEditing = true;
        $this->business_unit_id = $comp->business_unit_id;
        $this->code = $comp->code;
        $this->name = $comp->name;
        $this->type = $comp->type;
        $this->category = $comp->category;
        $this->apply_method = $comp->apply_method;
        $this->calculation_type = $comp->calculation_type;
        $this->employee_field_name = $comp->employee_field_name ?? '';
        $this->setting_key = $comp->setting_key ?? '';
        $this->percentage_base = $comp->percentage_base ?? 'gaji_pokok';
        $this->default_amount = $comp->default_amount ?? '';
        $this->is_taxable = $comp->is_taxable;
        $this->is_active = $comp->is_active;
        $this->sort_order = $comp->sort_order;
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->componentId = null;
        $this->isEditing = false;
        $this->business_unit_id = '';
        $this->code = '';
        $this->name = '';
        $this->type = 'earning';
        $this->category = 'tunjangan_tetap';
        $this->apply_method = 'template';
        $this->calculation_type = 'fixed';
        $this->employee_field_name = '';
        $this->setting_key = '';
        $this->percentage_base = 'gaji_pokok';
        $this->default_amount = '';
        $this->is_taxable = false;
        $this->is_active = true;
        $this->sort_order = 0;
        $this->resetValidation();
    }

    protected function rules(): array
    {
        return [
            'business_unit_id' => 'required|exists:business_units,id',
            'code' => [
                'required', 'string', 'max:30',
                Rule::unique('salary_components', 'code')
                    ->where('business_unit_id', $this->business_unit_id)
                    ->ignore($this->componentId),
            ],
            'name' => 'required|string|max:255',
            'type' => ['required', Rule::in(array_keys(SalaryComponent::TYPES))],
            'category' => ['required', Rule::in(array_keys(SalaryComponent::CATEGORIES))],
            'apply_method' => ['required', Rule::in(array_keys(SalaryComponent::APPLY_METHODS))],
            'calculation_type' => ['required', Rule::in(array_keys(SalaryComponent::CALCULATION_TYPES))],
            'employee_field_name' => 'nullable|string|max:50',
            'setting_key' => 'nullable|string|max:50',
            'percentage_base' => 'nullable|string|max:30',
            'default_amount' => 'nullable|numeric',
            'is_taxable' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ];
    }

    protected $messages = [
        'business_unit_id.required' => 'Unit usaha wajib dipilih.',
        'code.required' => 'Kode komponen wajib diisi.',
        'code.unique' => 'Kode sudah digunakan pada unit usaha ini.',
        'name.required' => 'Nama komponen wajib diisi.',
    ];

    public function save()
    {
        $this->business_unit_id = BusinessUnitService::resolveBusinessUnitId($this->business_unit_id);
        $this->validate();

        $data = [
            'business_unit_id' => $this->business_unit_id,
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type,
            'category' => $this->category,
            'apply_method' => $this->apply_method,
            'calculation_type' => $this->calculation_type,
            'employee_field_name' => $this->employee_field_name ?: null,
            'setting_key' => $this->setting_key ?: null,
            'percentage_base' => $this->percentage_base ?: null,
            'default_amount' => $this->default_amount !== '' ? (int) $this->default_amount : null,
            'is_taxable' => $this->is_taxable,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];

        if ($this->isEditing) {
            SalaryComponent::findOrFail($this->componentId)->update($data);
        } else {
            SalaryComponent::create($data);
        }

        $action = $this->isEditing ? 'diperbarui' : 'dibuat';
        $this->dispatch('alert', type: 'success', message: "Komponen '{$this->name}' berhasil {$action}.");
        $this->dispatch('refreshSalaryComponentList');
        $this->closeModal();
    }

    public function getUnitsProperty()
    {
        return BusinessUnitService::getAvailableUnits();
    }

    public function render()
    {
        return view('livewire.payroll.salary-component-form', [
            'units' => $this->units,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
