<?php

namespace App\Livewire\StockManagement;

use App\Models\SaldoProvider;
use App\Services\BusinessUnitService;
use App\Services\StockOpnameService;
use Livewire\Component;

class SaldoOpnameForm extends Component
{
    public bool $showModal = false;

    // Header fields
    public $business_unit_id = '';
    public $opname_date = '';
    public $pic_name = '';
    public $notes = '';

    // Details
    public array $details = [];

    protected $listeners = ['openSaldoOpnameModal'];

    public function openSaldoOpnameModal()
    {
        $this->resetForm();
        $this->business_unit_id = BusinessUnitService::getDefaultBusinessUnitId();
        $this->opname_date = date('Y-m-d');
        $this->showModal = true;
    }

    public function updatedBusinessUnitId()
    {
        $this->loadProviders();
    }

    public function loadProviders()
    {
        if (!$this->business_unit_id) {
            $this->details = [];
            return;
        }

        $providers = SaldoProvider::active()
            ->byBusinessUnit($this->business_unit_id)
            ->orderBy('name')
            ->get();

        $this->details = [];
        foreach ($providers as $provider) {
            $this->details[] = [
                'saldo_provider_id' => $provider->id,
                'provider_name' => $provider->name,
                'provider_code' => $provider->code,
                'system_balance' => (float) $provider->current_balance,
                'actual_balance' => (float) $provider->current_balance,
                'difference' => 0,
                'notes' => '',
            ];
        }
    }

    public function updatedDetails($value, $key)
    {
        $parts = explode('.', $key);
        if (count($parts) === 2 && $parts[1] === 'actual_balance') {
            $index = $parts[0];
            $systemBalance = (float) ($this->details[$index]['system_balance'] ?? 0);
            $actualBalance = (float) ($value ?: 0);
            $this->details[$index]['difference'] = $actualBalance - $systemBalance;
        }
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->business_unit_id = '';
        $this->opname_date = '';
        $this->pic_name = '';
        $this->notes = '';
        $this->details = [];
        $this->resetValidation();
    }

    protected function rules(): array
    {
        return [
            'business_unit_id' => 'required|exists:business_units,id',
            'opname_date' => 'required|date',
            'pic_name' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
            'details' => 'required|array|min:1',
            'details.*.saldo_provider_id' => 'required|exists:saldo_providers,id',
            'details.*.actual_balance' => 'required|numeric|min:0',
        ];
    }

    protected $messages = [
        'business_unit_id.required' => 'Unit usaha wajib dipilih.',
        'opname_date.required' => 'Tanggal opname wajib diisi.',
        'details.required' => 'Minimal 1 penyedia harus diisi.',
        'details.*.actual_balance.required' => 'Saldo aktual wajib diisi.',
    ];

    public function save()
    {
        $this->business_unit_id = BusinessUnitService::resolveBusinessUnitId($this->business_unit_id);
        $this->validate();

        $service = new StockOpnameService();

        $data = [
            'business_unit_id' => $this->business_unit_id,
            'opname_date' => $this->opname_date,
            'pic_name' => $this->pic_name ?: null,
            'notes' => $this->notes ?: null,
        ];

        $detailsToSave = [];
        foreach ($this->details as $detail) {
            $detailsToSave[] = [
                'saldo_provider_id' => $detail['saldo_provider_id'],
                'actual_balance' => $detail['actual_balance'],
                'notes' => $detail['notes'] ?? null,
            ];
        }

        $service->createSaldoOpname($data, $detailsToSave);

        $this->dispatch('alert', type: 'success', message: 'Saldo opname berhasil dibuat.');
        $this->dispatch('refreshSaldoOpnameList');
        $this->closeModal();
    }

    public function getUnitsProperty()
    {
        return BusinessUnitService::getAvailableUnits();
    }

    public function render()
    {
        return view('livewire.stock-management.saldo-opname-form', [
            'units' => $this->units,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
