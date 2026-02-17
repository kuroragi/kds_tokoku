<?php

namespace App\Livewire\NameCard;

use App\Models\Customer;
use App\Services\BusinessUnitService;
use Illuminate\Validation\Rule;
use Livewire\Component;

class CustomerForm extends Component
{
    public bool $showModal = false;
    public ?int $customerId = null;
    public bool $isEditing = false;

    public $business_unit_id = '';
    public $code = '';
    public $name = '';
    public $phone = '';
    public $email = '';
    public $address = '';
    public $city = '';
    public $contact_person = '';
    public $notes = '';
    public $is_active = true;

    protected $listeners = ['openCustomerModal', 'editCustomer'];

    public function openCustomerModal()
    {
        $this->resetForm();
        $this->business_unit_id = BusinessUnitService::getDefaultBusinessUnitId();
        $this->showModal = true;
    }

    public function editCustomer($id)
    {
        $customer = Customer::findOrFail($id);

        $this->customerId = $customer->id;
        $this->isEditing = true;
        $this->business_unit_id = $customer->business_unit_id;
        $this->code = $customer->code;
        $this->name = $customer->name;
        $this->phone = $customer->phone ?? '';
        $this->email = $customer->email ?? '';
        $this->address = $customer->address ?? '';
        $this->city = $customer->city ?? '';
        $this->contact_person = $customer->contact_person ?? '';
        $this->notes = $customer->notes ?? '';
        $this->is_active = $customer->is_active;
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->customerId = null;
        $this->isEditing = false;
        $this->business_unit_id = '';
        $this->code = '';
        $this->name = '';
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
                Rule::unique('customers', 'code')
                    ->where('business_unit_id', $this->business_unit_id)
                    ->ignore($this->customerId),
            ],
            'name' => 'required|string|max:255',
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
        'code.required' => 'Kode pelanggan wajib diisi.',
        'code.unique' => 'Kode pelanggan sudah digunakan pada unit usaha ini.',
        'name.required' => 'Nama pelanggan wajib diisi.',
    ];

    public function save()
    {
        $this->business_unit_id = BusinessUnitService::resolveBusinessUnitId($this->business_unit_id);
        $this->validate();

        $data = [
            'business_unit_id' => $this->business_unit_id,
            'code' => $this->code,
            'name' => $this->name,
            'phone' => $this->phone ?: null,
            'email' => $this->email ?: null,
            'address' => $this->address ?: null,
            'city' => $this->city ?: null,
            'contact_person' => $this->contact_person ?: null,
            'notes' => $this->notes ?: null,
            'is_active' => $this->is_active,
        ];

        if ($this->isEditing) {
            $customer = Customer::findOrFail($this->customerId);
            $customer->update($data);
        } else {
            Customer::create($data);
        }

        $action = $this->isEditing ? 'diperbarui' : 'dibuat';
        $this->dispatch('alert', type: 'success', message: "Pelanggan '{$this->name}' berhasil {$action}.");
        $this->dispatch('refreshCustomerList');
        $this->closeModal();
    }

    public function getUnitsProperty()
    {
        return BusinessUnitService::getAvailableUnits();
    }

    public function render()
    {
        return view('livewire.name-card.customer-form', [
            'units' => $this->units,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
