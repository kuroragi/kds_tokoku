<?php

namespace App\Livewire\ApAr;

use App\Models\COA;
use App\Models\Payable;
use App\Services\ApArService;
use App\Services\BusinessUnitService;
use Livewire\Component;

class PayablePaymentForm extends Component
{
    public bool $showModal = false;
    public ?int $payableId = null;

    public $payment_date = '';
    public $amount = '';
    public $payment_coa_id = '';
    public $reference = '';
    public $notes = '';

    // Display info
    public $payable_info = null;

    protected $listeners = ['openPayablePaymentModal'];

    public function openPayablePaymentModal($payableId)
    {
        $this->resetForm();
        $payable = Payable::with('vendor')->find($payableId);

        if (!$payable || $payable->status === 'paid' || $payable->status === 'void') {
            $this->dispatch('alert', type: 'error', message: 'Hutang tidak valid untuk pembayaran.');
            return;
        }

        $this->payableId = $payable->id;
        $this->payable_info = [
            'invoice_number' => $payable->invoice_number,
            'vendor_name' => $payable->vendor->name,
            'amount_due' => $payable->amount_due,
            'paid_amount' => $payable->paid_amount,
            'remaining' => $payable->remaining,
        ];
        $this->payment_date = now()->format('Y-m-d');
        $this->amount = $payable->remaining;
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->payableId = null;
        $this->payment_date = '';
        $this->amount = '';
        $this->payment_coa_id = '';
        $this->reference = '';
        $this->notes = '';
        $this->payable_info = null;
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
        'payment_date.required' => 'Tanggal pembayaran wajib diisi.',
        'amount.required' => 'Jumlah pembayaran wajib diisi.',
        'amount.min' => 'Jumlah pembayaran harus lebih dari 0.',
        'payment_coa_id.required' => 'Akun pembayaran (Kas/Bank) wajib dipilih.',
    ];

    public function save()
    {
        $this->validate();

        try {
            $payable = Payable::findOrFail($this->payableId);
            $service = app(ApArService::class);

            $service->createPayablePayment($payable, [
                'payment_date' => $this->payment_date,
                'amount' => (int) $this->amount,
                'payment_coa_id' => $this->payment_coa_id,
                'reference' => $this->reference ?: null,
                'notes' => $this->notes ?: null,
            ]);

            $this->dispatch('alert', type: 'success', message: "Pembayaran hutang '{$payable->invoice_number}' berhasil dicatat.");
            $this->dispatch('refreshPayableList');
            $this->closeModal();
        } catch (\Exception $e) {
            $this->dispatch('alert', type: 'error', message: $e->getMessage());
        }
    }

    public function getPaymentCoaOptionsProperty()
    {
        // Only show kas & bank accounts for payment
        return COA::where('is_active', true)
            ->where('is_leaf_account', true)
            ->where(function ($q) {
                $q->where('code', 'like', '1101%')  // Kas
                    ->orWhere('code', 'like', '1102%') // Bank
                    ->orWhere('code', 'like', '1103%');
            })
            ->orderBy('code')
            ->get();
    }

    public function render()
    {
        return view('livewire.apar.payable-payment-form', [
            'paymentCoaOptions' => $this->paymentCoaOptions,
        ]);
    }
}
