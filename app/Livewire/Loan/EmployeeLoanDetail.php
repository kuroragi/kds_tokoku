<?php

namespace App\Livewire\Loan;

use App\Models\COA;
use App\Models\EmployeeLoan;
use App\Models\EmployeeLoanPayment;
use App\Services\EmployeeLoanService;
use Livewire\Component;

class EmployeeLoanDetail extends Component
{
    public EmployeeLoan $loan;

    // Manual payment form
    public $showPaymentForm = false;
    public $paymentAmount = '';
    public $paymentDate = '';
    public $paymentCoaId = '';
    public $paymentReference = '';
    public $paymentNotes = '';

    protected $listeners = ['refreshEmployeeLoanDetail' => '$refresh'];

    public function mount(EmployeeLoan $loan)
    {
        $this->loan = $loan;
    }

    public function openPaymentForm()
    {
        $this->paymentAmount = $this->loan->installment_amount;
        if ($this->paymentAmount > $this->loan->remaining_amount) {
            $this->paymentAmount = $this->loan->remaining_amount;
        }
        $this->paymentDate = now()->toDateString();
        $this->paymentCoaId = '';
        $this->paymentReference = '';
        $this->paymentNotes = '';
        $this->showPaymentForm = true;
    }

    public function payFull()
    {
        $this->paymentAmount = $this->loan->remaining_amount;
        $this->paymentDate = now()->toDateString();
        $this->paymentCoaId = '';
        $this->paymentReference = '';
        $this->paymentNotes = 'Pelunasan penuh';
        $this->showPaymentForm = true;
    }

    public function submitPayment()
    {
        $this->validate([
            'paymentAmount' => 'required|numeric|min:1',
            'paymentDate' => 'required|date',
            'paymentCoaId' => 'required|exists:c_o_a_s,id',
        ], [
            'paymentAmount.required' => 'Jumlah pembayaran wajib diisi.',
            'paymentAmount.min' => 'Jumlah pembayaran minimal 1.',
            'paymentDate.required' => 'Tanggal pembayaran wajib diisi.',
            'paymentCoaId.required' => 'Akun pembayaran wajib dipilih.',
        ]);

        try {
            $service = app(EmployeeLoanService::class);
            $service->recordManualPayment($this->loan, [
                'amount' => (int) $this->paymentAmount,
                'payment_date' => $this->paymentDate,
                'payment_coa_id' => $this->paymentCoaId,
                'reference' => $this->paymentReference ?: null,
                'notes' => $this->paymentNotes ?: null,
            ]);

            $this->loan->refresh();
            $this->showPaymentForm = false;
            $this->dispatch('alert', type: 'success', message: 'Pembayaran berhasil dicatat.');
        } catch (\Exception $e) {
            $this->dispatch('alert', type: 'error', message: $e->getMessage());
        }
    }

    public function voidLoan()
    {
        try {
            $service = app(EmployeeLoanService::class);
            $service->voidLoan($this->loan);
            $this->loan->refresh();
            $this->dispatch('alert', type: 'success', message: 'Pinjaman berhasil dibatalkan.');
        } catch (\Exception $e) {
            $this->dispatch('alert', type: 'error', message: $e->getMessage());
        }
    }

    public function getCashAccountsProperty()
    {
        return COA::where('type', 'aktiva')
            ->where('is_parent', false)
            ->where(function ($q) {
                $q->where('code', 'like', '1101%')
                    ->orWhere('code', 'like', '1103%');
            })
            ->orderBy('code')
            ->get();
    }

    public function getPaymentsProperty()
    {
        return EmployeeLoanPayment::where('employee_loan_id', $this->loan->id)
            ->orderByDesc('payment_date')
            ->get();
    }

    public function render()
    {
        return view('livewire.loan.employee-loan-detail', [
            'payments' => $this->payments,
            'cashAccounts' => $this->cashAccounts,
        ]);
    }
}
