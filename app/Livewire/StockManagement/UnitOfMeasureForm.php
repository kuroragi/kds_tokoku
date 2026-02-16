<?php

namespace App\Livewire\StockManagement;

use App\Models\UnitOfMeasure;
use App\Services\BusinessUnitService;
use Illuminate\Validation\Rule;
use Livewire\Component;

class UnitOfMeasureForm extends Component
{
    public bool $showModal = false;
    public ?int $measureId = null;
    public bool $isEditing = false;

    // Fields
    public $business_unit_id = '';
    public $code = '';
    public $name = '';
    public $symbol = '';
    public $description = '';
    public $is_active = true;

    protected $listeners = ['openUnitOfMeasureModal', 'editUnitOfMeasure'];

    public function openUnitOfMeasureModal()
    {
        $this->resetForm();
        $this->business_unit_id = BusinessUnitService::getDefaultBusinessUnitId();
        $this->showModal = true;
    }

    public function editUnitOfMeasure($id)
    {
        $measure = UnitOfMeasure::findOrFail($id);

        $this->measureId = $measure->id;
        $this->isEditing = true;
        $this->business_unit_id = $measure->business_unit_id;
        $this->code = $measure->code;
        $this->name = $measure->name;
        $this->symbol = $measure->symbol ?? '';
        $this->description = $measure->description ?? '';
        $this->is_active = $measure->is_active;
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->measureId = null;
        $this->isEditing = false;
        $this->business_unit_id = '';
        $this->code = '';
        $this->name = '';
        $this->symbol = '';
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
                Rule::unique('unit_of_measures', 'code')
                    ->where('business_unit_id', $this->business_unit_id)
                    ->ignore($this->measureId),
            ],
            'name' => 'required|string|max:255',
            'symbol' => 'nullable|string|max:10',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ];
    }

    protected $messages = [
        'business_unit_id.required' => 'Unit usaha wajib dipilih.',
        'code.required' => 'Kode satuan wajib diisi.',
        'code.unique' => 'Kode satuan sudah digunakan pada unit usaha ini.',
        'name.required' => 'Nama satuan wajib diisi.',
    ];

    public function save()
    {
        $this->business_unit_id = BusinessUnitService::resolveBusinessUnitId($this->business_unit_id);
        $this->validate();

        $data = [
            'business_unit_id' => $this->business_unit_id,
            'code' => $this->code,
            'name' => $this->name,
            'symbol' => $this->symbol ?: null,
            'description' => $this->description ?: null,
            'is_system_default' => false,
            'is_active' => $this->is_active,
        ];

        if ($this->isEditing) {
            $measure = UnitOfMeasure::findOrFail($this->measureId);
            $measure->update($data);
        } else {
            UnitOfMeasure::create($data);
        }

        $action = $this->isEditing ? 'diperbarui' : 'dibuat';
        $this->dispatch('alert', type: 'success', message: "Satuan '{$this->name}' berhasil {$action}.");
        $this->dispatch('refreshUnitOfMeasureList');
        $this->closeModal();
    }

    public function getUnitsProperty()
    {
        return BusinessUnitService::getAvailableUnits();
    }

    public function render()
    {
        return view('livewire.stock-management.unit-of-measure-form', [
            'units' => $this->units,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
