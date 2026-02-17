<?php

namespace App\Livewire\NameCard;

use App\Models\Vendor;
use App\Services\BusinessUnitService;
use Illuminate\Validation\Rule;
use Livewire\Component;

class VendorForm extends Component
{
    public bool $showModal = false;
    public ?int $vendorId = null;
    public bool $isEditing = false;

    public $business_unit_id = '';
    public $code = '';
    public $name = '';
    public $type = 'lainnya';
    public $phone = '';
    public $email = '';
    public $address = '';
    public $city = '';
    public $contact_person = '';
    public $npwp = '';
    public $nik = '';
    public $is_pph23 = false;
    public $pph23_rate = '2.00';
    public $bank_name = '';
    public $bank_account_number = '';
    public $bank_account_name = '';
    public $website = '';
    public $notes = '';
    public $is_active = true;

    protected $listeners = ['openVendorModal', 'editVendor'];

    public function openVendorModal()
    {
        $this->resetForm();
        $this->business_unit_id = BusinessUnitService::getDefaultBusinessUnitId();
        $this->showModal = true;
    }

    public function editVendor($id)
    {
        $vendor = Vendor::findOrFail($id);

        $this->vendorId = $vendor->id;
        $this->isEditing = true;
        $this->code = $vendor->code;
        $this->name = $vendor->name;
        $this->type = $vendor->type;
        $this->phone = $vendor->phone ?? '';
        $this->email = $vendor->email ?? '';
        $this->address = $vendor->address ?? '';
        $this->city = $vendor->city ?? '';
        $this->contact_person = $vendor->contact_person ?? '';
        $this->npwp = $vendor->npwp ?? '';
        $this->nik = $vendor->nik ?? '';
        $this->is_pph23 = $vendor->is_pph23;
        $this->pph23_rate = $vendor->pph23_rate ?? '2.00';
        $this->bank_name = $vendor->bank_name ?? '';
        $this->bank_account_number = $vendor->bank_account_number ?? '';
        $this->bank_account_name = $vendor->bank_account_name ?? '';
        $this->website = $vendor->website ?? '';
        $this->notes = $vendor->notes ?? '';
        $this->is_active = $vendor->is_active;

        // For edit, show the first attached unit (for non-superadmin context)
        if (!BusinessUnitService::isSuperAdmin()) {
            $this->business_unit_id = BusinessUnitService::getUserBusinessUnitId() ?? '';
        }

        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->vendorId = null;
        $this->isEditing = false;
        $this->business_unit_id = '';
        $this->code = '';
        $this->name = '';
        $this->type = 'lainnya';
        $this->phone = '';
        $this->email = '';
        $this->address = '';
        $this->city = '';
        $this->contact_person = '';
        $this->npwp = '';
        $this->nik = '';
        $this->is_pph23 = false;
        $this->pph23_rate = '2.00';
        $this->bank_name = '';
        $this->bank_account_number = '';
        $this->bank_account_name = '';
        $this->website = '';
        $this->notes = '';
        $this->is_active = true;
        $this->resetValidation();
    }

    protected function rules(): array
    {
        return [
            'code' => [
                'required', 'string', 'max:30',
                Rule::unique('vendors', 'code')->ignore($this->vendorId),
            ],
            'name' => 'required|string|max:255',
            'type' => 'required|in:distributor,supplier_bahan,jasa,lainnya',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:1000',
            'city' => 'nullable|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'npwp' => 'nullable|string|max:30',
            'nik' => 'nullable|string|max:20',
            'is_pph23' => 'boolean',
            'pph23_rate' => 'nullable|numeric|min:0|max:100',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:30',
            'bank_account_name' => 'nullable|string|max:255',
            'website' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ];
    }

    protected $messages = [
        'code.required' => 'Kode vendor wajib diisi.',
        'code.unique' => 'Kode vendor sudah digunakan.',
        'name.required' => 'Nama vendor wajib diisi.',
        'type.required' => 'Tipe vendor wajib dipilih.',
    ];

    public function save()
    {
        $this->validate();

        $data = [
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type,
            'phone' => $this->phone ?: null,
            'email' => $this->email ?: null,
            'address' => $this->address ?: null,
            'city' => $this->city ?: null,
            'contact_person' => $this->contact_person ?: null,
            'npwp' => $this->npwp ?: null,
            'nik' => $this->nik ?: null,
            'is_pph23' => $this->is_pph23,
            'pph23_rate' => $this->is_pph23 ? $this->pph23_rate : 2.00,
            'bank_name' => $this->bank_name ?: null,
            'bank_account_number' => $this->bank_account_number ?: null,
            'bank_account_name' => $this->bank_account_name ?: null,
            'website' => $this->website ?: null,
            'notes' => $this->notes ?: null,
            'is_active' => $this->is_active,
        ];

        if ($this->isEditing) {
            $vendor = Vendor::findOrFail($this->vendorId);
            $vendor->update($data);
        } else {
            $vendor = Vendor::create($data);

            // Auto-attach to business unit
            $unitId = BusinessUnitService::resolveBusinessUnitId($this->business_unit_id);
            if ($unitId) {
                $vendor->businessUnits()->syncWithoutDetaching([$unitId]);
            }
        }

        $action = $this->isEditing ? 'diperbarui' : 'dibuat';
        $this->dispatch('alert', type: 'success', message: "Vendor '{$this->name}' berhasil {$action}.");
        $this->dispatch('refreshVendorList');
        $this->closeModal();
    }

    public function getUnitsProperty()
    {
        return BusinessUnitService::getAvailableUnits();
    }

    public function render()
    {
        return view('livewire.name-card.vendor-form', [
            'units' => $this->units,
            'types' => Vendor::getTypes(),
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
