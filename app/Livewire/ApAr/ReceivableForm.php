<?php

namespace App\Livewire\ApAr;

use App\Models\COA;
use App\Models\Customer;
use App\Models\Receivable;
use App\Services\ApArService;
use App\Services\BusinessUnitService;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ReceivableForm extends Component
{
    public bool $showModal = false;
    public ?int $receivableId = null;
    public bool $isEditing = false;

    public $business_unit_id = '';
    public $customer_id = '';
    public $invoice_number = '';
    public $invoice_date = '';
    public $due_date = '';
    public $description = '';
    public $credit_coa_id = '';
    public $amount = '';
    public $notes = '';

    protected $listeners = ['openReceivableModal', 'editReceivable'];

    public function openReceivableModal()
    {
        $this->resetForm();
        $this->business_unit_id = BusinessUnitService::getDefaultBusinessUnitId();
        $this->invoice_date = now()->format('Y-m-d');
        $this->due_date = now()->addDays(30)->format('Y-m-d');
        $this->showModal = true;
    }

    public function editReceivable($id)
    {
        $receivable = Receivable::findOrFail($id);

        if ($receivable->status !== 'unpaid') {
            $this->dispatch('alert', type: 'error', message: 'Hanya piutang berstatus "Belum Diterima" yang bisa diedit.');
            return;
        }

        $this->receivableId = $receivable->id;
        $this->isEditing = true;
        $this->business_unit_id = $receivable->business_unit_id;
        $this->customer_id = $receivable->customer_id;
        $this->invoice_number = $receivable->invoice_number;
        $this->invoice_date = $receivable->invoice_date->format('Y-m-d');
        $this->due_date = $receivable->due_date->format('Y-m-d');
        $this->description = $receivable->description ?? '';
        $this->credit_coa_id = $receivable->credit_coa_id ?? '';
        $this->amount = $receivable->amount;
        $this->notes = $receivable->notes ?? '';

        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->receivableId = null;
        $this->isEditing = false;
        $this->business_unit_id = '';
        $this->customer_id = '';
        $this->invoice_number = '';
        $this->invoice_date = '';
        $this->due_date = '';
        $this->description = '';
        $this->credit_coa_id = '';
        $this->amount = '';
        $this->notes = '';
        $this->resetValidation();
    }

    protected function rules(): array
    {
        return [
            'business_unit_id' => 'required|exists:business_units,id',
            'customer_id' => 'required|exists:customers,id',
            'invoice_number' => [
                'required', 'string', 'max:50',
                Rule::unique('receivables')->where('business_unit_id', $this->business_unit_id)->ignore($this->receivableId),
            ],
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'description' => 'nullable|string|max:255',
            'credit_coa_id' => 'required|exists:c_o_a_s,id',
            'amount' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    protected $messages = [
        'business_unit_id.required' => 'Unit usaha wajib dipilih.',
        'customer_id.required' => 'Pelanggan wajib dipilih.',
        'invoice_number.required' => 'Nomor faktur wajib diisi.',
        'invoice_number.unique' => 'Nomor faktur sudah digunakan pada unit ini.',
        'invoice_date.required' => 'Tanggal faktur wajib diisi.',
        'due_date.required' => 'Tanggal jatuh tempo wajib diisi.',
        'due_date.after_or_equal' => 'Jatuh tempo harus setelah atau sama dengan tanggal faktur.',
        'credit_coa_id.required' => 'Akun pendapatan wajib dipilih.',
        'amount.required' => 'Jumlah wajib diisi.',
        'amount.min' => 'Jumlah harus lebih dari 0.',
    ];

    public function save()
    {
        $this->validate();

        try {
            if ($this->isEditing) {
                $receivable = Receivable::findOrFail($this->receivableId);
                $receivable->update([
                    'customer_id' => $this->customer_id,
                    'invoice_number' => $this->invoice_number,
                    'invoice_date' => $this->invoice_date,
                    'due_date' => $this->due_date,
                    'description' => $this->description ?: null,
                    'credit_coa_id' => $this->credit_coa_id ?: null,
                    'amount' => (int) $this->amount,
                    'notes' => $this->notes ?: null,
                ]);
                $action = 'diperbarui';
            } else {
                $service = app(ApArService::class);
                $service->createReceivable([
                    'business_unit_id' => $this->business_unit_id,
                    'customer_id' => $this->customer_id,
                    'invoice_number' => $this->invoice_number,
                    'invoice_date' => $this->invoice_date,
                    'due_date' => $this->due_date,
                    'description' => $this->description ?: null,
                    'credit_coa_id' => $this->credit_coa_id ?: null,
                    'amount' => (int) $this->amount,
                    'notes' => $this->notes ?: null,
                ]);
                $action = 'dibuat';
            }

            $this->dispatch('alert', type: 'success', message: "Piutang '{$this->invoice_number}' berhasil {$action}.");
            $this->dispatch('refreshReceivableList');
            $this->closeModal();
        } catch (\Exception $e) {
            $this->dispatch('alert', type: 'error', message: $e->getMessage());
        }
    }

    public function getUnitsProperty()
    {
        return BusinessUnitService::getAvailableUnits();
    }

    public function getCustomersProperty()
    {
        $query = Customer::active();
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
        return view('livewire.apar.receivable-form', [
            'units' => $this->units,
            'customers' => $this->customers,
            'coaOptions' => $this->coaOptions,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
