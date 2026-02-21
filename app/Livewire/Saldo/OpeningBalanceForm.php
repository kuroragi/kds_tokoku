<?php

namespace App\Livewire\Saldo;

use App\Models\BusinessUnit;
use App\Models\COA;
use App\Models\OpeningBalance;
use App\Models\Period;
use App\Services\BusinessUnitService;
use App\Services\OpeningBalanceService;
use Livewire\Component;

class OpeningBalanceForm extends Component
{
    public $showModal = false;
    public $isEditing = false;
    public $openingBalanceId = null;

    public $business_unit_id = '';
    public $period_id = '';
    public $balance_date = '';
    public $description = '';

    // Entries: array of ['coa_id' => X, 'coa_code' => '', 'coa_name' => '', 'debit' => 0, 'credit' => 0, 'notes' => '']
    public $entries = [];

    // Search filter for COA list
    public $coaSearch = '';

    protected $listeners = [
        'openOpeningBalanceModal' => 'openModal',
        'editOpeningBalance' => 'loadForEdit',
    ];

    public function openModal()
    {
        $this->resetForm();
        $this->showModal = true;
        $this->business_unit_id = BusinessUnitService::getDefaultBusinessUnitId();
        $this->balance_date = date('Y-m-d');

        if ($this->business_unit_id) {
            $this->loadCoaEntries();
        }
    }

    public function loadForEdit($id)
    {
        $this->resetForm();
        $openingBalance = OpeningBalance::with('entries.coa')->findOrFail($id);

        if ($openingBalance->isPosted()) {
            $this->dispatch('alert', type: 'error', message: 'Saldo awal yang sudah diposting tidak dapat diedit.');
            return;
        }

        $this->isEditing = true;
        $this->openingBalanceId = $openingBalance->id;
        $this->business_unit_id = $openingBalance->business_unit_id;
        $this->period_id = $openingBalance->period_id;
        $this->balance_date = $openingBalance->balance_date->format('Y-m-d');
        $this->description = $openingBalance->description;

        // Load entries from existing data and merge with all available COAs
        $this->loadCoaEntries();

        // Fill in existing values
        $service = new OpeningBalanceService();
        $existingEntries = $service->getEntriesForForm($openingBalance);

        foreach ($this->entries as $idx => $entry) {
            $coaId = $entry['coa_id'];
            if (isset($existingEntries[$coaId])) {
                $this->entries[$idx]['debit'] = $existingEntries[$coaId]['debit'];
                $this->entries[$idx]['credit'] = $existingEntries[$coaId]['credit'];
                $this->entries[$idx]['notes'] = $existingEntries[$coaId]['notes'];
            }
        }

        $this->showModal = true;
    }

    public function updatedBusinessUnitId()
    {
        $this->loadCoaEntries();
    }

    public function loadCoaEntries()
    {
        if (!$this->business_unit_id) {
            $this->entries = [];
            return;
        }

        $service = new OpeningBalanceService();
        $coas = $service->getAvailableCoaAccounts((int) $this->business_unit_id);

        $this->entries = $coas->map(fn($coa) => [
            'coa_id' => $coa->id,
            'coa_code' => $coa->code,
            'coa_name' => $coa->name,
            'coa_type' => $coa->type,
            'is_mapped' => $coa->is_mapped ?? false,
            'debit' => 0,
            'credit' => 0,
            'notes' => '',
        ])->toArray();
    }

    public function getIsSuperAdminProperty(): bool
    {
        return BusinessUnitService::isSuperAdmin();
    }

    public function getUnitsProperty()
    {
        return BusinessUnitService::getAvailableUnits();
    }

    public function getPeriodsProperty()
    {
        return Period::orderBy('start_date', 'desc')->get();
    }

    public function getTotalDebitProperty(): float
    {
        return collect($this->entries)->sum(fn($e) => (float) ($e['debit'] ?? 0));
    }

    public function getTotalCreditProperty(): float
    {
        return collect($this->entries)->sum(fn($e) => (float) ($e['credit'] ?? 0));
    }

    public function getDifferenceProperty(): float
    {
        return abs($this->totalDebit - $this->totalCredit);
    }

    public function getIsBalancedProperty(): bool
    {
        return $this->difference < 0.01;
    }

    public function getFilteredEntriesProperty(): array
    {
        if (!$this->coaSearch) {
            return $this->entries;
        }

        $search = strtolower($this->coaSearch);
        return array_filter($this->entries, function ($entry) use ($search) {
            return str_contains(strtolower($entry['coa_code']), $search)
                || str_contains(strtolower($entry['coa_name']), $search);
        });
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->isEditing = false;
        $this->openingBalanceId = null;
        $this->business_unit_id = '';
        $this->period_id = '';
        $this->balance_date = '';
        $this->description = '';
        $this->entries = [];
        $this->coaSearch = '';
        $this->resetValidation();
    }

    protected function rules(): array
    {
        return [
            'business_unit_id' => 'required|exists:business_units,id',
            'period_id' => 'required|exists:periods,id',
            'balance_date' => 'required|date',
            'description' => 'nullable|string|max:500',
        ];
    }

    protected $messages = [
        'business_unit_id.required' => 'Unit usaha wajib dipilih.',
        'period_id.required' => 'Periode wajib dipilih.',
        'balance_date.required' => 'Tanggal saldo awal wajib diisi.',
    ];

    public function save()
    {
        $this->business_unit_id = BusinessUnitService::resolveBusinessUnitId($this->business_unit_id);
        $this->validate();

        // Check at least one entry has amount
        $hasEntries = collect($this->entries)->contains(fn($e) => ((float) ($e['debit'] ?? 0)) > 0 || ((float) ($e['credit'] ?? 0)) > 0);
        if (!$hasEntries) {
            $this->dispatch('alert', type: 'error', message: 'Minimal 1 akun harus memiliki saldo.');
            return;
        }

        $service = new OpeningBalanceService();

        $data = [
            'business_unit_id' => $this->business_unit_id,
            'period_id' => $this->period_id,
            'balance_date' => $this->balance_date,
            'description' => $this->description ?: 'Saldo Awal',
        ];

        $service->saveOpeningBalance($data, $this->entries);

        $this->dispatch('alert', type: 'success', message: 'Saldo awal berhasil disimpan.');
        $this->dispatch('refreshOpeningBalanceList');
        $this->closeModal();
    }

    public function render()
    {
        return view('livewire.saldo.opening-balance-form');
    }
}
