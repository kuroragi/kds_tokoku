<?php

namespace App\Livewire\Bank;

use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\BankFeeMatrix;
use App\Models\CashAccount;
use App\Models\FundTransfer;
use App\Services\BankService;
use App\Services\BusinessUnitService;
use Livewire\Component;

class FundTransferForm extends Component
{
    public bool $showModal = false;
    public ?int $transferId = null;
    public bool $isEditing = false;

    // Fields
    public $business_unit_id = '';
    public $source_type = 'cash';
    public $source_bank_account_id = '';
    public $destination_type = 'bank';
    public $destination_bank_account_id = '';
    public $amount = 0;
    public $admin_fee = 0;
    public $transfer_date = '';
    public $reference_no = '';
    public $notes = '';

    protected $listeners = ['openFundTransferModal', 'editFundTransfer'];

    public function openFundTransferModal()
    {
        $this->resetForm();
        $this->business_unit_id = BusinessUnitService::getDefaultBusinessUnitId();
        $this->transfer_date = date('Y-m-d');
        $this->showModal = true;
    }

    public function editFundTransfer($id)
    {
        $transfer = FundTransfer::findOrFail($id);

        $this->transferId = $transfer->id;
        $this->isEditing = true;
        $this->business_unit_id = $transfer->business_unit_id;
        $this->source_type = $transfer->source_type;
        $this->source_bank_account_id = $transfer->source_bank_account_id ?? '';
        $this->destination_type = $transfer->destination_type;
        $this->destination_bank_account_id = $transfer->destination_bank_account_id ?? '';
        $this->amount = $transfer->amount;
        $this->admin_fee = $transfer->admin_fee;
        $this->transfer_date = $transfer->transfer_date->format('Y-m-d');
        $this->reference_no = $transfer->reference_no ?? '';
        $this->notes = $transfer->notes ?? '';
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->transferId = null;
        $this->isEditing = false;
        $this->business_unit_id = '';
        $this->source_type = 'cash';
        $this->source_bank_account_id = '';
        $this->destination_type = 'bank';
        $this->destination_bank_account_id = '';
        $this->amount = 0;
        $this->admin_fee = 0;
        $this->transfer_date = '';
        $this->reference_no = '';
        $this->notes = '';
        $this->resetValidation();
    }

    /**
     * Auto-fill admin fee from fee matrix when source and destination bank accounts change.
     */
    public function updatedSourceBankAccountId()
    {
        $this->lookupFee();
    }

    public function updatedDestinationBankAccountId()
    {
        $this->lookupFee();
    }

    private function lookupFee()
    {
        if ($this->source_type !== 'bank' || $this->destination_type !== 'bank') {
            return;
        }

        if (!$this->source_bank_account_id || !$this->destination_bank_account_id) {
            return;
        }

        $sourceAccount = BankAccount::find($this->source_bank_account_id);
        $destAccount = BankAccount::find($this->destination_bank_account_id);

        if (!$sourceAccount || !$destAccount) {
            return;
        }

        $fee = BankFeeMatrix::findFee($sourceAccount->bank_id, $destAccount->bank_id, 'online');
        if ($fee !== null) {
            $this->admin_fee = $fee;
        }
    }

    protected function rules(): array
    {
        $rules = [
            'business_unit_id' => 'required|exists:business_units,id',
            'source_type' => 'required|in:cash,bank',
            'destination_type' => 'required|in:cash,bank',
            'amount' => 'required|numeric|min:1',
            'admin_fee' => 'required|numeric|min:0',
            'transfer_date' => 'required|date',
            'reference_no' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ];

        if ($this->source_type === 'bank') {
            $rules['source_bank_account_id'] = 'required|exists:bank_accounts,id';
        }
        if ($this->destination_type === 'bank') {
            $rules['destination_bank_account_id'] = 'required|exists:bank_accounts,id';
        }

        return $rules;
    }

    protected $messages = [
        'business_unit_id.required' => 'Unit usaha wajib dipilih.',
        'source_type.required' => 'Tipe sumber wajib dipilih.',
        'destination_type.required' => 'Tipe tujuan wajib dipilih.',
        'source_bank_account_id.required' => 'Rekening sumber wajib dipilih.',
        'destination_bank_account_id.required' => 'Rekening tujuan wajib dipilih.',
        'amount.required' => 'Jumlah transfer wajib diisi.',
        'amount.min' => 'Jumlah transfer minimal 1.',
        'admin_fee.required' => 'Biaya admin wajib diisi.',
        'admin_fee.min' => 'Biaya admin tidak boleh negatif.',
        'transfer_date.required' => 'Tanggal transfer wajib diisi.',
    ];

    public function save()
    {
        $this->business_unit_id = BusinessUnitService::resolveBusinessUnitId($this->business_unit_id);
        $this->validate();

        $data = [
            'business_unit_id' => $this->business_unit_id,
            'source_type' => $this->source_type,
            'source_bank_account_id' => $this->source_type === 'bank' ? $this->source_bank_account_id : null,
            'destination_type' => $this->destination_type,
            'destination_bank_account_id' => $this->destination_type === 'bank' ? $this->destination_bank_account_id : null,
            'amount' => $this->amount,
            'admin_fee' => $this->admin_fee,
            'transfer_date' => $this->transfer_date,
            'reference_no' => $this->reference_no ?: null,
            'notes' => $this->notes ?: null,
        ];

        $service = new BankService();

        if ($this->isEditing) {
            $oldTransfer = FundTransfer::findOrFail($this->transferId);
            $service->deleteTransfer($oldTransfer);
            $service->createTransfer($data);
        } else {
            $service->createTransfer($data);
        }

        $action = $this->isEditing ? 'diperbarui' : 'dibuat';
        $this->dispatch('alert', type: 'success', message: "Transfer dana berhasil {$action}.");
        $this->dispatch('refreshFundTransferList');
        $this->dispatch('refreshBankAccountList');
        $this->closeModal();
    }

    // ─── Computed Properties ───

    public function getUnitsProperty()
    {
        return BusinessUnitService::getAvailableUnits();
    }

    public function getSourceAccountsProperty()
    {
        if ($this->source_type !== 'bank') {
            return collect();
        }

        $query = BankAccount::with('bank')->active();
        if ($this->business_unit_id) {
            $query->byBusinessUnit($this->business_unit_id);
        }
        return $query->orderBy('account_name')->get();
    }

    public function getDestinationAccountsProperty()
    {
        if ($this->destination_type !== 'bank') {
            return collect();
        }

        $query = BankAccount::with('bank')->active();
        if ($this->business_unit_id) {
            $query->byBusinessUnit($this->business_unit_id);
        }
        return $query->orderBy('account_name')->get();
    }

    public function getSourceBalanceProperty()
    {
        if ($this->source_type === 'cash' && $this->business_unit_id) {
            $cash = CashAccount::where('business_unit_id', $this->business_unit_id)->first();
            return $cash ? $cash->current_balance : 0;
        }

        if ($this->source_type === 'bank' && $this->source_bank_account_id) {
            $account = BankAccount::find($this->source_bank_account_id);
            return $account ? $account->current_balance : 0;
        }

        return 0;
    }

    public function render()
    {
        return view('livewire.bank.fund-transfer-form', [
            'units' => $this->units,
            'sourceAccounts' => $this->sourceAccounts,
            'destinationAccounts' => $this->destinationAccounts,
            'sourceBalance' => $this->sourceBalance,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
