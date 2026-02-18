<?php

namespace App\Livewire\Saldo;

use App\Models\SaldoProvider;
use App\Models\SaldoTopup;
use App\Services\BusinessUnitService;
use App\Services\SaldoService;
use Livewire\Component;

class SaldoTopupForm extends Component
{
    public bool $showModal = false;
    public ?int $topupId = null;
    public bool $isEditing = false;

    // Fields
    public $business_unit_id = '';
    public $saldo_provider_id = '';
    public $amount = 0;
    public $fee = 0;
    public $topup_date = '';
    public $method = 'transfer';
    public $reference_no = '';
    public $notes = '';

    protected $listeners = ['openSaldoTopupModal', 'editSaldoTopup'];

    public function openSaldoTopupModal()
    {
        $this->resetForm();
        $this->business_unit_id = BusinessUnitService::getDefaultBusinessUnitId();
        $this->topup_date = date('Y-m-d');
        $this->showModal = true;
    }

    public function editSaldoTopup($id)
    {
        $topup = SaldoTopup::findOrFail($id);

        $this->topupId = $topup->id;
        $this->isEditing = true;
        $this->business_unit_id = $topup->business_unit_id;
        $this->saldo_provider_id = $topup->saldo_provider_id;
        $this->amount = $topup->amount;
        $this->fee = $topup->fee;
        $this->topup_date = $topup->topup_date->format('Y-m-d');
        $this->method = $topup->method;
        $this->reference_no = $topup->reference_no ?? '';
        $this->notes = $topup->notes ?? '';
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->topupId = null;
        $this->isEditing = false;
        $this->business_unit_id = '';
        $this->saldo_provider_id = '';
        $this->amount = 0;
        $this->fee = 0;
        $this->topup_date = '';
        $this->method = 'transfer';
        $this->reference_no = '';
        $this->notes = '';
        $this->resetValidation();
    }

    protected function rules(): array
    {
        return [
            'business_unit_id' => 'required|exists:business_units,id',
            'saldo_provider_id' => 'required|exists:saldo_providers,id',
            'amount' => 'required|numeric|min:1',
            'fee' => 'nullable|numeric|min:0',
            'topup_date' => 'required|date',
            'method' => 'required|in:transfer,cash,e-wallet,other',
            'reference_no' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    protected $messages = [
        'business_unit_id.required' => 'Unit usaha wajib dipilih.',
        'saldo_provider_id.required' => 'Penyedia saldo wajib dipilih.',
        'amount.required' => 'Jumlah top up wajib diisi.',
        'amount.min' => 'Jumlah top up minimal 1.',
        'topup_date.required' => 'Tanggal top up wajib diisi.',
        'method.required' => 'Metode pembayaran wajib dipilih.',
    ];

    public function save()
    {
        $this->business_unit_id = BusinessUnitService::resolveBusinessUnitId($this->business_unit_id);
        $this->validate();

        $data = [
            'business_unit_id' => $this->business_unit_id,
            'saldo_provider_id' => $this->saldo_provider_id,
            'amount' => $this->amount,
            'fee' => $this->fee ?: 0,
            'topup_date' => $this->topup_date,
            'method' => $this->method,
            'reference_no' => $this->reference_no ?: null,
            'notes' => $this->notes ?: null,
        ];

        $service = new SaldoService();

        if ($this->isEditing) {
            // Reverse old topup, delete, create new
            $oldTopup = SaldoTopup::findOrFail($this->topupId);
            $service->deleteTopup($oldTopup);
            $service->createTopup($data);
        } else {
            $service->createTopup($data);
        }

        $action = $this->isEditing ? 'diperbarui' : 'dibuat';
        $this->dispatch('alert', type: 'success', message: "Top up saldo berhasil {$action}.");
        $this->dispatch('refreshSaldoTopupList');
        $this->dispatch('refreshSaldoProviderList');
        $this->closeModal();
    }

    public function getUnitsProperty()
    {
        return BusinessUnitService::getAvailableUnits();
    }

    public function getAvailableProvidersProperty()
    {
        $query = SaldoProvider::active();
        if ($this->business_unit_id) {
            $query->where('business_unit_id', $this->business_unit_id);
        }
        return $query->orderBy('name')->get();
    }

    public function render()
    {
        return view('livewire.saldo.saldo-topup-form', [
            'units' => $this->units,
            'availableProviders' => $this->availableProviders,
            'methods' => SaldoTopup::getMethods(),
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
