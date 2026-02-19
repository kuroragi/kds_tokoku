<?php

namespace App\Livewire\Purchase;

use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Services\PurchaseService;
use Livewire\Component;

class PurchasePaymentForm extends Component
{
    public bool $showModal = false;
    public ?int $purchaseId = null;
    public $purchaseInfo = null;

    // Fields
    public $amount = 0;
    public $payment_date = '';
    public $payment_method = 'cash';
    public $reference_no = '';
    public $notes = '';

    protected $listeners = ['openPurchasePaymentModal'];

    public function openPurchasePaymentModal($purchaseId)
    {
        $this->resetForm();
        $this->purchaseId = $purchaseId;
        $this->purchaseInfo = Purchase::with(['vendor', 'payments'])->findOrFail($purchaseId);
        $this->payment_date = date('Y-m-d');
        $this->amount = (float) $this->purchaseInfo->remaining_amount;
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->purchaseId = null;
        $this->purchaseInfo = null;
        $this->amount = 0;
        $this->payment_date = '';
        $this->payment_method = 'cash';
        $this->reference_no = '';
        $this->notes = '';
        $this->resetValidation();
    }

    protected function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:1',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,bank_transfer,e-wallet,other',
            'reference_no' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    protected $messages = [
        'amount.required' => 'Jumlah pembayaran wajib diisi.',
        'amount.min' => 'Jumlah pembayaran minimal 1.',
        'payment_date.required' => 'Tanggal pembayaran wajib diisi.',
        'payment_method.required' => 'Metode pembayaran wajib dipilih.',
    ];

    public function save()
    {
        $this->validate();

        $purchase = Purchase::findOrFail($this->purchaseId);
        $service = new PurchaseService();

        $service->recordPayment($purchase, [
            'amount' => $this->amount,
            'payment_date' => $this->payment_date,
            'payment_method' => $this->payment_method,
            'reference_no' => $this->reference_no ?: null,
            'notes' => $this->notes ?: null,
        ]);

        $this->dispatch('alert', type: 'success', message: 'Pembayaran berhasil dicatat.');
        $this->dispatch('refreshPurchaseList');
        $this->closeModal();
    }

    public function render()
    {
        return view('livewire.purchase.purchase-payment-form', [
            'methods' => PurchasePayment::getMethods(),
        ]);
    }
}
