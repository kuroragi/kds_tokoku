<?php

namespace App\Livewire\Sales;

use App\Models\Sale;
use App\Models\SalePayment;
use App\Services\SalesService;
use Livewire\Component;

class SalePaymentForm extends Component
{
    public bool $showModal = false;
    public ?int $saleId = null;
    public $saleInfo = null;

    // Fields
    public $amount = 0;
    public $payment_date = '';
    public $payment_method = 'cash';
    public $payment_source = 'kas_utama';
    public $reference_no = '';
    public $notes = '';

    protected $listeners = ['openSalePaymentModal'];

    /**
     * Auto-set payment_source based on payment_method.
     */
    public function updatedPaymentMethod($value)
    {
        $this->payment_source = in_array($value, ['bank_transfer', 'giro', 'e_wallet'])
            ? 'bank_utama'
            : 'kas_utama';
    }

    public function openSalePaymentModal($saleId)
    {
        $this->resetForm();
        $this->saleId = $saleId;
        $this->saleInfo = Sale::with(['customer', 'payments'])->findOrFail($saleId);
        $this->payment_date = date('Y-m-d');
        $this->amount = (float) $this->saleInfo->remaining_amount;
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->saleId = null;
        $this->saleInfo = null;
        $this->amount = 0;
        $this->payment_date = '';
        $this->payment_method = 'cash';
        $this->payment_source = 'kas_utama';
        $this->reference_no = '';
        $this->notes = '';
        $this->resetValidation();
    }

    protected function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:1',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,bank_transfer,giro,e_wallet,other',
            'payment_source' => 'required|in:kas_utama,kas_kecil,bank_utama',
            'reference_no' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    protected $messages = [
        'amount.required' => 'Jumlah pembayaran wajib diisi.',
        'amount.min' => 'Jumlah pembayaran minimal 1.',
        'payment_date.required' => 'Tanggal pembayaran wajib diisi.',
        'payment_method.required' => 'Metode pembayaran wajib dipilih.',
        'payment_source.required' => 'Sumber pembayaran wajib dipilih.',
    ];

    public function save()
    {
        $this->validate();

        $sale = Sale::findOrFail($this->saleId);
        $service = new SalesService();

        $service->recordPayment($sale, [
            'amount' => $this->amount,
            'payment_date' => $this->payment_date,
            'payment_method' => $this->payment_method,
            'payment_source' => $this->payment_source,
            'reference_no' => $this->reference_no ?: null,
            'notes' => $this->notes ?: null,
        ]);

        $this->dispatch('alert', type: 'success', message: 'Pembayaran berhasil dicatat.');
        $this->dispatch('refreshSaleList');
        $this->closeModal();
    }

    public function render()
    {
        return view('livewire.sales.sale-payment-form', [
            'methods' => SalePayment::getMethods(),
        ]);
    }
}
