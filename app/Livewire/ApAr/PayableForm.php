<?php

namespace App\Livewire\ApAr;

use App\Models\COA;
use App\Models\Payable;
use App\Models\Vendor;
use App\Services\ApArService;
use App\Services\BusinessUnitService;
use Illuminate\Validation\Rule;
use Livewire\Component;

class PayableForm extends Component
{
    public bool $showModal = false;
    public ?int $payableId = null;
    public bool $isEditing = false;

    public $business_unit_id = '';
    public $vendor_id = '';
    public $invoice_number = '';
    public $invoice_date = '';
    public $due_date = '';
    public $description = '';
    public $debit_coa_id = '';
    public $input_amount = '';
    public $is_net_basis = false;
    public $notes = '';

    // Computed display
    public $vendor_is_pph23 = false;
    public $vendor_pph23_rate = 0;
    public $calc_dpp = 0;
    public $calc_pph23 = 0;
    public $calc_amount_due = 0;

    protected $listeners = ['openPayableModal', 'editPayable'];

    public function openPayableModal()
    {
        $this->resetForm();
        $this->business_unit_id = BusinessUnitService::getDefaultBusinessUnitId();
        $this->invoice_date = now()->format('Y-m-d');
        $this->due_date = now()->addDays(30)->format('Y-m-d');
        $this->showModal = true;
    }

    public function editPayable($id)
    {
        $payable = Payable::findOrFail($id);

        if ($payable->status !== 'unpaid') {
            $this->dispatch('alert', type: 'error', message: 'Hanya hutang berstatus "Belum Dibayar" yang bisa diedit.');
            return;
        }

        $this->payableId = $payable->id;
        $this->isEditing = true;
        $this->business_unit_id = $payable->business_unit_id;
        $this->vendor_id = $payable->vendor_id;
        $this->invoice_number = $payable->invoice_number;
        $this->invoice_date = $payable->invoice_date->format('Y-m-d');
        $this->due_date = $payable->due_date->format('Y-m-d');
        $this->description = $payable->description ?? '';
        $this->debit_coa_id = $payable->debit_coa_id ?? '';
        $this->input_amount = $payable->input_amount;
        $this->is_net_basis = $payable->is_net_basis;
        $this->notes = $payable->notes ?? '';

        $this->updatedVendorId();
        $this->recalculate();
        $this->showModal = true;
    }

    public function updatedVendorId()
    {
        if ($this->vendor_id) {
            $vendor = Vendor::find($this->vendor_id);
            if ($vendor) {
                $this->vendor_is_pph23 = $vendor->is_pph23;
                $this->vendor_pph23_rate = (float) $vendor->pph23_rate;
                if (!$this->isEditing) {
                    $this->is_net_basis = $vendor->is_net_pph23;
                }
                $this->recalculate();
                return;
            }
        }
        $this->vendor_is_pph23 = false;
        $this->vendor_pph23_rate = 0;
        $this->recalculate();
    }

    public function updatedInputAmount()
    {
        $this->recalculate();
    }

    public function updatedIsNetBasis()
    {
        $this->recalculate();
    }

    protected function recalculate()
    {
        $amount = (int) ($this->input_amount ?: 0);
        $rate = $this->vendor_is_pph23 ? $this->vendor_pph23_rate : 0;

        $calc = ApArService::calculatePph23($amount, $rate, $this->is_net_basis);
        $this->calc_dpp = $calc['dpp'];
        $this->calc_pph23 = $calc['pph23_amount'];
        $this->calc_amount_due = $calc['amount_due'];
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->payableId = null;
        $this->isEditing = false;
        $this->business_unit_id = '';
        $this->vendor_id = '';
        $this->invoice_number = '';
        $this->invoice_date = '';
        $this->due_date = '';
        $this->description = '';
        $this->debit_coa_id = '';
        $this->input_amount = '';
        $this->is_net_basis = false;
        $this->notes = '';
        $this->vendor_is_pph23 = false;
        $this->vendor_pph23_rate = 0;
        $this->calc_dpp = 0;
        $this->calc_pph23 = 0;
        $this->calc_amount_due = 0;
        $this->resetValidation();
    }

    protected function rules(): array
    {
        return [
            'business_unit_id' => 'required|exists:business_units,id',
            'vendor_id' => 'required|exists:vendors,id',
            'invoice_number' => [
                'required', 'string', 'max:50',
                Rule::unique('payables')->where('business_unit_id', $this->business_unit_id)->ignore($this->payableId),
            ],
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'description' => 'nullable|string|max:255',
            'debit_coa_id' => 'required|exists:c_o_a_s,id',
            'input_amount' => 'required|integer|min:1',
            'is_net_basis' => 'boolean',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    protected $messages = [
        'business_unit_id.required' => 'Unit usaha wajib dipilih.',
        'vendor_id.required' => 'Vendor wajib dipilih.',
        'invoice_number.required' => 'Nomor faktur wajib diisi.',
        'invoice_number.unique' => 'Nomor faktur sudah digunakan pada unit ini.',
        'invoice_date.required' => 'Tanggal faktur wajib diisi.',
        'due_date.required' => 'Tanggal jatuh tempo wajib diisi.',
        'due_date.after_or_equal' => 'Jatuh tempo harus setelah atau sama dengan tanggal faktur.',
        'debit_coa_id.required' => 'Akun debit wajib dipilih.',
        'input_amount.required' => 'Jumlah wajib diisi.',
        'input_amount.min' => 'Jumlah harus lebih dari 0.',
    ];

    public function save()
    {
        $this->validate();

        try {
            if ($this->isEditing) {
                $payable = Payable::findOrFail($this->payableId);
                $vendor = Vendor::findOrFail($this->vendor_id);
                $rate = $vendor->is_pph23 ? (float) $vendor->pph23_rate : 0;
                $calc = ApArService::calculatePph23((int) $this->input_amount, $rate, $this->is_net_basis);

                $payable->update([
                    'vendor_id' => $this->vendor_id,
                    'invoice_number' => $this->invoice_number,
                    'invoice_date' => $this->invoice_date,
                    'due_date' => $this->due_date,
                    'description' => $this->description ?: null,
                    'debit_coa_id' => $this->debit_coa_id ?: null,
                    'input_amount' => (int) $this->input_amount,
                    'is_net_basis' => $this->is_net_basis,
                    'dpp' => $calc['dpp'],
                    'pph23_rate' => $rate,
                    'pph23_amount' => $calc['pph23_amount'],
                    'amount_due' => $calc['amount_due'],
                    'notes' => $this->notes ?: null,
                ]);
                $action = 'diperbarui';
            } else {
                $service = app(ApArService::class);
                $service->createPayable([
                    'business_unit_id' => $this->business_unit_id,
                    'vendor_id' => $this->vendor_id,
                    'invoice_number' => $this->invoice_number,
                    'invoice_date' => $this->invoice_date,
                    'due_date' => $this->due_date,
                    'description' => $this->description ?: null,
                    'debit_coa_id' => $this->debit_coa_id ?: null,
                    'input_amount' => (int) $this->input_amount,
                    'is_net_basis' => $this->is_net_basis,
                    'notes' => $this->notes ?: null,
                ]);
                $action = 'dibuat';
            }

            $this->dispatch('alert', type: 'success', message: "Hutang '{$this->invoice_number}' berhasil {$action}.");
            $this->dispatch('refreshPayableList');
            $this->closeModal();
        } catch (\Exception $e) {
            $this->dispatch('alert', type: 'error', message: $e->getMessage());
        }
    }

    public function getUnitsProperty()
    {
        return BusinessUnitService::getAvailableUnits();
    }

    public function getVendorsProperty()
    {
        $query = Vendor::active();
        if ($this->business_unit_id) {
            $query->byBusinessUnit($this->business_unit_id);
        }
        return $query->orderBy('name')->get();
    }

    public function getCoaOptionsProperty()
    {
        return COA::where('is_active', true)
            ->where('is_leaf_account', true)
            ->orderBy('code')
            ->get();
    }

    public function render()
    {
        return view('livewire.apar.payable-form', [
            'units' => $this->units,
            'vendors' => $this->vendors,
            'coaOptions' => $this->coaOptions,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
