<?php

namespace App\Livewire\NameCard;

use App\Models\Position;
use App\Services\BusinessUnitService;
use Illuminate\Validation\Rule;
use Livewire\Component;

class PositionForm extends Component
{
    public bool $showModal = false;
    public ?int $positionId = null;
    public bool $isEditing = false;

    public $business_unit_id = '';
    public $code = '';
    public $name = '';
    public $description = '';
    public $is_active = true;

    protected $listeners = ['openPositionModal', 'editPosition'];

    public function openPositionModal()
    {
        $this->resetForm();
        $this->business_unit_id = BusinessUnitService::getDefaultBusinessUnitId();
        $this->showModal = true;
    }

    public function editPosition($id)
    {
        $position = Position::findOrFail($id);

        $this->positionId = $position->id;
        $this->isEditing = true;
        $this->business_unit_id = $position->business_unit_id;
        $this->code = $position->code;
        $this->name = $position->name;
        $this->description = $position->description ?? '';
        $this->is_active = $position->is_active;
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->positionId = null;
        $this->isEditing = false;
        $this->business_unit_id = '';
        $this->code = '';
        $this->name = '';
        $this->description = '';
        $this->is_active = true;
        $this->resetValidation();
    }

    protected function rules(): array
    {
        return [
            'business_unit_id' => 'required|exists:business_units,id',
            'code' => [
                'required', 'string', 'max:20',
                Rule::unique('positions', 'code')
                    ->where('business_unit_id', $this->business_unit_id)
                    ->ignore($this->positionId),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ];
    }

    protected $messages = [
        'business_unit_id.required' => 'Unit usaha wajib dipilih.',
        'code.required' => 'Kode jabatan wajib diisi.',
        'code.unique' => 'Kode jabatan sudah digunakan pada unit usaha ini.',
        'name.required' => 'Nama jabatan wajib diisi.',
    ];

    public function save()
    {
        $this->business_unit_id = BusinessUnitService::resolveBusinessUnitId($this->business_unit_id);
        $this->validate();

        $data = [
            'business_unit_id' => $this->business_unit_id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description ?: null,
            'is_system_default' => false,
            'is_active' => $this->is_active,
        ];

        if ($this->isEditing) {
            $position = Position::findOrFail($this->positionId);
            $position->update($data);
        } else {
            Position::create($data);
        }

        $action = $this->isEditing ? 'diperbarui' : 'dibuat';
        $this->dispatch('alert', type: 'success', message: "Jabatan '{$this->name}' berhasil {$action}.");
        $this->dispatch('refreshPositionList');
        $this->closeModal();
    }

    public function getUnitsProperty()
    {
        return BusinessUnitService::getAvailableUnits();
    }

    public function render()
    {
        return view('livewire.name-card.position-form', [
            'units' => $this->units,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
