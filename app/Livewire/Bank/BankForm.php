<?php

namespace App\Livewire\Bank;

use App\Models\Bank;
use App\Models\BankFeeMatrix;
use Illuminate\Validation\Rule;
use Livewire\Component;

class BankForm extends Component
{
    public bool $showModal = false;
    public ?int $bankId = null;
    public bool $isEditing = false;

    // Bank fields
    public $code = '';
    public $name = '';
    public $swift_code = '';
    public $is_active = true;

    // Fee Matrix fields
    public bool $showFeeModal = false;
    public ?int $feeId = null;
    public bool $isEditingFee = false;
    public $source_bank_id = '';
    public $destination_bank_id = '';
    public $transfer_type = 'online';
    public $fee = 0;
    public $fee_notes = '';
    public $fee_is_active = true;

    protected $listeners = ['openBankModal', 'editBank', 'openFeeMatrixModal', 'editFeeMatrix'];

    // ─── Bank Modal Methods ───

    public function openBankModal()
    {
        $this->resetBankForm();
        $this->showModal = true;
    }

    public function editBank($id)
    {
        $bank = Bank::findOrFail($id);
        $this->bankId = $bank->id;
        $this->isEditing = true;
        $this->code = $bank->code;
        $this->name = $bank->name;
        $this->swift_code = $bank->swift_code ?? '';
        $this->is_active = $bank->is_active;
        $this->showModal = true;
    }

    public function closeBankModal()
    {
        $this->showModal = false;
        $this->resetBankForm();
    }

    private function resetBankForm()
    {
        $this->bankId = null;
        $this->isEditing = false;
        $this->code = '';
        $this->name = '';
        $this->swift_code = '';
        $this->is_active = true;
        $this->resetValidation();
    }

    protected function bankRules(): array
    {
        return [
            'code' => [
                'required', 'string', 'max:20',
                Rule::unique('banks', 'code')->ignore($this->bankId),
            ],
            'name' => 'required|string|max:255',
            'swift_code' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ];
    }

    protected $messages = [
        'code.required' => 'Kode bank wajib diisi.',
        'code.unique' => 'Kode bank sudah digunakan.',
        'name.required' => 'Nama bank wajib diisi.',
        'source_bank_id.required' => 'Bank asal wajib dipilih.',
        'destination_bank_id.required' => 'Bank tujuan wajib dipilih.',
        'transfer_type.required' => 'Tipe transfer wajib dipilih.',
        'fee.required' => 'Biaya wajib diisi.',
        'fee.min' => 'Biaya tidak boleh negatif.',
    ];

    public function saveBank()
    {
        $this->validate($this->bankRules());

        $data = [
            'code' => $this->code,
            'name' => $this->name,
            'swift_code' => $this->swift_code ?: null,
            'is_active' => $this->is_active,
        ];

        if ($this->isEditing) {
            $bank = Bank::findOrFail($this->bankId);
            $bank->update($data);
        } else {
            Bank::create($data);
        }

        $action = $this->isEditing ? 'diperbarui' : 'dibuat';
        $this->dispatch('alert', type: 'success', message: "Bank '{$this->name}' berhasil {$action}.");
        $this->dispatch('refreshBankList');
        $this->closeBankModal();
    }

    // ─── Fee Matrix Modal Methods ───

    public function openFeeMatrixModal()
    {
        $this->resetFeeForm();
        $this->showFeeModal = true;
    }

    public function editFeeMatrix($id)
    {
        $matrix = BankFeeMatrix::findOrFail($id);
        $this->feeId = $matrix->id;
        $this->isEditingFee = true;
        $this->source_bank_id = $matrix->source_bank_id;
        $this->destination_bank_id = $matrix->destination_bank_id;
        $this->transfer_type = $matrix->transfer_type;
        $this->fee = $matrix->fee;
        $this->fee_notes = $matrix->notes ?? '';
        $this->fee_is_active = $matrix->is_active;
        $this->showFeeModal = true;
    }

    public function closeFeeModal()
    {
        $this->showFeeModal = false;
        $this->resetFeeForm();
    }

    private function resetFeeForm()
    {
        $this->feeId = null;
        $this->isEditingFee = false;
        $this->source_bank_id = '';
        $this->destination_bank_id = '';
        $this->transfer_type = 'online';
        $this->fee = 0;
        $this->fee_notes = '';
        $this->fee_is_active = true;
        $this->resetValidation();
    }

    protected function feeRules(): array
    {
        return [
            'source_bank_id' => 'required|exists:banks,id',
            'destination_bank_id' => 'required|exists:banks,id',
            'transfer_type' => 'required|in:online,bi-fast,rtgs,sknbi,other',
            'fee' => 'required|numeric|min:0',
            'fee_notes' => 'nullable|string|max:1000',
            'fee_is_active' => 'boolean',
        ];
    }

    public function saveFee()
    {
        $this->validate($this->feeRules());

        $data = [
            'source_bank_id' => $this->source_bank_id,
            'destination_bank_id' => $this->destination_bank_id,
            'transfer_type' => $this->transfer_type,
            'fee' => $this->fee,
            'notes' => $this->fee_notes ?: null,
            'is_active' => $this->fee_is_active,
        ];

        if ($this->isEditingFee) {
            $matrix = BankFeeMatrix::findOrFail($this->feeId);
            $matrix->update($data);
        } else {
            BankFeeMatrix::create($data);
        }

        $action = $this->isEditingFee ? 'diperbarui' : 'dibuat';
        $this->dispatch('alert', type: 'success', message: "Fee matrix berhasil {$action}.");
        $this->dispatch('refreshBankList');
        $this->closeFeeModal();
    }

    // ─── Computed Properties ───

    public function getAvailableBanksProperty()
    {
        return Bank::active()->orderBy('name')->get();
    }

    public function render()
    {
        return view('livewire.bank.bank-form', [
            'availableBanks' => $this->availableBanks,
            'transferTypes' => BankFeeMatrix::getTransferTypes(),
        ]);
    }
}
