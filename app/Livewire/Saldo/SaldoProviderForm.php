<?php

namespace App\Livewire\Saldo;

use App\Models\SaldoProvider;
use App\Services\BusinessUnitService;
use Illuminate\Validation\Rule;
use Livewire\Component;

class SaldoProviderForm extends Component
{
    public bool $showModal = false;
    public ?int $providerId = null;
    public bool $isEditing = false;

    // Fields
    public $business_unit_id = '';
    public $code = '';
    public $name = '';
    public $type = 'e-wallet';
    public $description = '';
    public $initial_balance = 0;
    public $is_active = true;

    protected $listeners = ['openSaldoProviderModal', 'editSaldoProvider'];

    public function openSaldoProviderModal()
    {
        $this->resetForm();
        $this->business_unit_id = BusinessUnitService::getDefaultBusinessUnitId();
        $this->showModal = true;
    }

    public function editSaldoProvider($id)
    {
        $provider = SaldoProvider::findOrFail($id);

        $this->providerId = $provider->id;
        $this->isEditing = true;
        $this->business_unit_id = $provider->business_unit_id;
        $this->code = $provider->code;
        $this->name = $provider->name;
        $this->type = $provider->type;
        $this->description = $provider->description ?? '';
        $this->initial_balance = $provider->initial_balance;
        $this->is_active = $provider->is_active;
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->providerId = null;
        $this->isEditing = false;
        $this->business_unit_id = '';
        $this->code = '';
        $this->name = '';
        $this->type = 'e-wallet';
        $this->description = '';
        $this->initial_balance = 0;
        $this->is_active = true;
        $this->resetValidation();
    }

    protected function rules(): array
    {
        return [
            'business_unit_id' => 'required|exists:business_units,id',
            'code' => [
                'required', 'string', 'max:20',
                Rule::unique('saldo_providers', 'code')
                    ->where('business_unit_id', $this->business_unit_id)
                    ->ignore($this->providerId),
            ],
            'name' => 'required|string|max:255',
            'type' => 'required|in:e-wallet,bank,other',
            'description' => 'nullable|string|max:1000',
            'initial_balance' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ];
    }

    protected $messages = [
        'business_unit_id.required' => 'Unit usaha wajib dipilih.',
        'code.required' => 'Kode penyedia wajib diisi.',
        'code.unique' => 'Kode penyedia sudah digunakan pada unit usaha ini.',
        'name.required' => 'Nama penyedia wajib diisi.',
        'type.required' => 'Tipe penyedia wajib dipilih.',
        'initial_balance.required' => 'Saldo awal wajib diisi.',
        'initial_balance.min' => 'Saldo awal tidak boleh negatif.',
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
            'description' => $this->description ?: null,
            'initial_balance' => $this->initial_balance,
            'is_active' => $this->is_active,
        ];

        if ($this->isEditing) {
            $provider = SaldoProvider::findOrFail($this->providerId);
            $oldInitial = $provider->initial_balance;
            $data['current_balance'] = $provider->current_balance + ($this->initial_balance - $oldInitial);
            $provider->update($data);
        } else {
            $data['current_balance'] = $this->initial_balance;
            SaldoProvider::create($data);
        }

        $action = $this->isEditing ? 'diperbarui' : 'dibuat';
        $this->dispatch('alert', type: 'success', message: "Penyedia saldo '{$this->name}' berhasil {$action}.");
        $this->dispatch('refreshSaldoProviderList');
        $this->closeModal();
    }

    public function getUnitsProperty()
    {
        return BusinessUnitService::getAvailableUnits();
    }

    public function render()
    {
        return view('livewire.saldo.saldo-provider-form', [
            'units' => $this->units,
            'types' => SaldoProvider::getTypes(),
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
