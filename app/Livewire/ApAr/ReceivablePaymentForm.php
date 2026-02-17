<?php

namespace App\Livewire\ApAr;

use App\Models\COA;
use App\Models\Receivable;
use App\Services\ApArService;
use App\Services\BusinessUnitService;
use Livewire\Component;

class ReceivablePaymentForm extends Component
{
    public bool $showModal = false;
    public ?int $receivableId = null;

    public $payment_date = '';
    public $amount = '';
    public $payment_coa_id = '';
    public $reference = '';
    public $notes = '';

    // Display info
    public $receivable_info = null;

    protected $listeners = ['openReceivablePaymentModal'];

    public function openReceivablePaymentModal($receivableId)
    {
        $this->resetForm();
        $receivable = Receivable::with('customer')->find($receivableId);

        if (!$receivable || $receivable->status === 'paid' || $receivable->status === 'void') {
            $this->dispatch('alert', type: 'error', message: 'Piutang tidak valid untuk penerimaan.');
            return;
        }

        $this->receivableId = $receivable->id;
        $this->receivable_info = [
            'invoice_number' => $receivable->invoice_number,
            'customer_name' => $receivable->customer->name,
            'amount' => $receivable->amount,
            'paid_amount' => $receivable->paid_amount,
            'remaining' => $receivable->remaining,
        ];
        $this->payment_date = now()->format('Y-m-d');
        $this->amount = $receivable->remaining;
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
        $this->payment_date = '';
        $this->amount = '';
        $this->payment_coa_id = '';
        $this->reference = '';
        $this->notes = '';
        $this->receivable_info = null;
        $this->resetValidation();
    }

    protected function rules(): array
    {
        return [
            'payment_date' => 'required|date',
            'amount' => 'required|integer|min:1',
            'payment_coa_id' => 'required|exists:c_o_a_s,id',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    protected $messages = [
        'payment_date.required' => 'Tanggal penerimaan wajib diisi.',
        'amount.required' => 'Jumlah penerimaan wajib diisi.',
        'amount.min' => 'Jumlah penerimaan harus lebih dari 0.',
        'payment_coa_id.required' => 'Akun penerimaan (Kas/Bank) wajib dipilih.',
    ];

    public function save()
    {
        $this->validate();

        try {
            $receivable = Receivable::findOrFail($this->receivableId);
            $service = app(ApArService::class);

            $service->createReceivablePayment($receivable, [
                'payment_date' => $this->payment_date,
                'amount' => (int) $this->amount,
                'payment_coa_id' => $this->payment_coa_id,
                'reference' => $this->reference ?: null,
                'notes' => $this->notes ?: null,
            ]);

            $this->dispatch('alert', type: 'success', message: "Penerimaan piutang '{$receivable->invoice_number}' berhasil dicatat.");
            $this->dispatch('refreshReceivableList');
            $this->closeModal();
        } catch (\Exception $e) {
            $this->dispatch('alert', type: 'error', message: $e->getMessage());
        }
    }

    public function getPaymentCoaOptionsProperty()
    {
        return COA::where('is_active', true)
            ->where('is_leaf_account', true)
            ->where(function ($q) {
                $q->where('code', 'like', '1101%')
                    ->orWhere('code', 'like', '1102%')
                    ->orWhere('code', 'like', '1103%');
            })
            ->orderBy('code')
            ->get();
    }

    public function render()
    {
        return view('livewire.apar.receivable-payment-form', [
            'paymentCoaOptions' => $this->paymentCoaOptions,
        ]);
    }
}
