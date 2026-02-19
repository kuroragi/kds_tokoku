<?php

namespace App\Livewire\Bank;

use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\CashAccount;
use App\Services\BusinessUnitService;
use Illuminate\Validation\Rule;
use Livewire\Component;

class BankAccountForm extends Component
{
    public bool $showModal = false;
    public ?int $accountId = null;
    public bool $isEditing = false;

    // Bank Account fields
    public $business_unit_id = '';
    public $bank_id = '';
    public $account_number = '';
    public $account_name = '';
    public $description = '';
    public $initial_balance = 0;
    public $is_active = true;

    // Cash Account editing
    public bool $showCashModal = false;
    public ?int $cashAccountId = null;
    public $cash_name = '';
    public $cash_initial_balance = 0;

    protected $listeners = ['openBankAccountModal', 'editBankAccount', 'editCashAccount'];

    // ─── Bank Account Modal ───

    public function openBankAccountModal()
    {
        $this->resetForm();
        $this->business_unit_id = BusinessUnitService::getDefaultBusinessUnitId();
        $this->showModal = true;
    }

    public function editBankAccount($id)
    {
        $account = BankAccount::findOrFail($id);
        $this->accountId = $account->id;
        $this->isEditing = true;
        $this->business_unit_id = $account->business_unit_id;
        $this->bank_id = $account->bank_id;
        $this->account_number = $account->account_number;
        $this->account_name = $account->account_name;
        $this->description = $account->description ?? '';
        $this->initial_balance = $account->initial_balance;
        $this->is_active = $account->is_active;
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->accountId = null;
        $this->isEditing = false;
        $this->business_unit_id = '';
        $this->bank_id = '';
        $this->account_number = '';
        $this->account_name = '';
        $this->description = '';
        $this->initial_balance = 0;
        $this->is_active = true;
        $this->resetValidation();
    }

    protected function rules(): array
    {
        return [
            'business_unit_id' => 'required|exists:business_units,id',
            'bank_id' => 'required|exists:banks,id',
            'account_number' => [
                'required', 'string', 'max:50',
                Rule::unique('bank_accounts', 'account_number')
                    ->where('business_unit_id', $this->business_unit_id)
                    ->where('bank_id', $this->bank_id)
                    ->ignore($this->accountId),
            ],
            'account_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'initial_balance' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ];
    }

    protected $messages = [
        'business_unit_id.required' => 'Unit usaha wajib dipilih.',
        'bank_id.required' => 'Bank wajib dipilih.',
        'account_number.required' => 'Nomor rekening wajib diisi.',
        'account_number.unique' => 'Nomor rekening sudah ada pada bank dan unit usaha ini.',
        'account_name.required' => 'Nama pemilik rekening wajib diisi.',
        'initial_balance.required' => 'Saldo awal wajib diisi.',
        'initial_balance.min' => 'Saldo awal tidak boleh negatif.',
    ];

    public function save()
    {
        $this->business_unit_id = BusinessUnitService::resolveBusinessUnitId($this->business_unit_id);
        $this->validate();

        $data = [
            'business_unit_id' => $this->business_unit_id,
            'bank_id' => $this->bank_id,
            'account_number' => $this->account_number,
            'account_name' => $this->account_name,
            'description' => $this->description ?: null,
            'initial_balance' => $this->initial_balance,
            'is_active' => $this->is_active,
        ];

        if ($this->isEditing) {
            $account = BankAccount::findOrFail($this->accountId);
            $oldInitial = $account->initial_balance;
            $data['current_balance'] = $account->current_balance + ($this->initial_balance - $oldInitial);
            $account->update($data);
        } else {
            $data['current_balance'] = $this->initial_balance;
            BankAccount::create($data);
        }

        $action = $this->isEditing ? 'diperbarui' : 'dibuat';
        $this->dispatch('alert', type: 'success', message: "Rekening '{$this->account_name}' berhasil {$action}.");
        $this->dispatch('refreshBankAccountList');
        $this->closeModal();
    }

    // ─── Cash Account Modal ───

    public function editCashAccount($id)
    {
        $cash = CashAccount::findOrFail($id);
        $this->cashAccountId = $cash->id;
        $this->cash_name = $cash->name;
        $this->cash_initial_balance = $cash->initial_balance;
        $this->showCashModal = true;
    }

    public function closeCashModal()
    {
        $this->showCashModal = false;
        $this->cashAccountId = null;
        $this->cash_name = '';
        $this->cash_initial_balance = 0;
        $this->resetValidation();
    }

    public function saveCashAccount()
    {
        $this->validate([
            'cash_name' => 'required|string|max:255',
            'cash_initial_balance' => 'required|numeric|min:0',
        ], [
            'cash_name.required' => 'Nama kas wajib diisi.',
            'cash_initial_balance.required' => 'Saldo awal wajib diisi.',
            'cash_initial_balance.min' => 'Saldo awal tidak boleh negatif.',
        ]);

        $cash = CashAccount::findOrFail($this->cashAccountId);
        $oldInitial = $cash->initial_balance;
        $cash->update([
            'name' => $this->cash_name,
            'initial_balance' => $this->cash_initial_balance,
            'current_balance' => $cash->current_balance + ($this->cash_initial_balance - $oldInitial),
        ]);

        $this->dispatch('alert', type: 'success', message: "Kas '{$this->cash_name}' berhasil diperbarui.");
        $this->dispatch('refreshBankAccountList');
        $this->closeCashModal();
    }

    // ─── Computed Properties ───

    public function getUnitsProperty()
    {
        return BusinessUnitService::getAvailableUnits();
    }

    public function getAvailableBanksProperty()
    {
        return Bank::active()->orderBy('name')->get();
    }

    public function render()
    {
        return view('livewire.bank.bank-account-form', [
            'units' => $this->units,
            'availableBanks' => $this->availableBanks,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
