<?php

namespace App\Livewire\NameCard;

use App\Models\Partner;
use App\Services\BusinessUnitService;
use Illuminate\Validation\Rule;
use Livewire\Component;

class PartnerForm extends Component
{
    public bool $showModal = false;
    public ?int $partnerId = null;
    public bool $isEditing = false;

    public $business_unit_id = '';
    public $code = '';
    public $name = '';
    public $type = '';
    public $phone = '';
    public $email = '';
    public $address = '';
    public $city = '';
    public $contact_person = '';
    public $notes = '';
    public $is_active = true;

    protected $listeners = ['openPartnerModal', 'editPartner'];

    public function openPartnerModal()
    {
        $this->resetForm();
        $this->business_unit_id = BusinessUnitService::getDefaultBusinessUnitId();
        $this->showModal = true;
    }

    public function editPartner($id)
    {
        $partner = Partner::findOrFail($id);

        $this->partnerId = $partner->id;
        $this->isEditing = true;
        $this->business_unit_id = $partner->business_unit_id;
        $this->code = $partner->code;
        $this->name = $partner->name;
        $this->type = $partner->type ?? '';
        $this->phone = $partner->phone ?? '';
        $this->email = $partner->email ?? '';
        $this->address = $partner->address ?? '';
        $this->city = $partner->city ?? '';
        $this->contact_person = $partner->contact_person ?? '';
        $this->notes = $partner->notes ?? '';
        $this->is_active = $partner->is_active;
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->partnerId = null;
        $this->isEditing = false;
        $this->business_unit_id = '';
        $this->code = '';
        $this->name = '';
        $this->type = '';
        $this->phone = '';
        $this->email = '';
        $this->address = '';
        $this->city = '';
        $this->contact_person = '';
        $this->notes = '';
        $this->is_active = true;
        $this->resetValidation();
    }

    protected function rules(): array
    {
        return [
            'business_unit_id' => 'required|exists:business_units,id',
            'code' => [
                'required', 'string', 'max:30',
                Rule::unique('partners', 'code')
                    ->where('business_unit_id', $this->business_unit_id)
                    ->ignore($this->partnerId),
            ],
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:1000',
            'city' => 'nullable|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ];
    }

    protected $messages = [
        'business_unit_id.required' => 'Unit usaha wajib dipilih.',
        'code.required' => 'Kode partner wajib diisi.',
        'code.unique' => 'Kode partner sudah digunakan pada unit usaha ini.',
        'name.required' => 'Nama partner wajib diisi.',
    ];

    public function save()
    {
        $this->business_unit_id = BusinessUnitService::resolveBusinessUnitId($this->business_unit_id);
        $this->validate();

        $data = [
            'business_unit_id' => $this->business_unit_id,
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type ?: null,
            'phone' => $this->phone ?: null,
            'email' => $this->email ?: null,
            'address' => $this->address ?: null,
            'city' => $this->city ?: null,
            'contact_person' => $this->contact_person ?: null,
            'notes' => $this->notes ?: null,
            'is_active' => $this->is_active,
        ];

        if ($this->isEditing) {
            $partner = Partner::findOrFail($this->partnerId);
            $partner->update($data);
        } else {
            Partner::create($data);
        }

        $action = $this->isEditing ? 'diperbarui' : 'dibuat';
        $this->dispatch('alert', type: 'success', message: "Partner '{$this->name}' berhasil {$action}.");
        $this->dispatch('refreshPartnerList');
        $this->closeModal();
    }

    public function getUnitsProperty()
    {
        return BusinessUnitService::getAvailableUnits();
    }

    public function render()
    {
        return view('livewire.name-card.partner-form', [
            'units' => $this->units,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
