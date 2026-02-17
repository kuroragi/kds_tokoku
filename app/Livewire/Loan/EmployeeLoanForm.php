<?php

namespace App\Livewire\Loan;

use App\Models\COA;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Services\BusinessUnitService;
use App\Services\EmployeeLoanService;
use Livewire\Component;

class EmployeeLoanForm extends Component
{
    public $showModal = false;
    public $editMode = false;
    public $loanId = null;

    public $business_unit_id = '';
    public $employee_id = '';
    public $loan_number = '';
    public $description = '';
    public $loan_amount = '';
    public $installment_count = '';
    public $disbursed_date = '';
    public $start_deduction_date = '';
    public $payment_coa_id = '';
    public $notes = '';

    protected $listeners = ['openEmployeeLoanForm'];

    protected function rules()
    {
        return [
            'business_unit_id' => 'required|exists:business_units,id',
            'employee_id' => 'required|exists:employees,id',
            'loan_amount' => 'required|numeric|min:1',
            'installment_count' => 'required|integer|min:1|max:60',
            'disbursed_date' => 'required|date',
            'payment_coa_id' => 'required|exists:c_o_a_s,id',
        ];
    }

    protected function messages()
    {
        return [
            'business_unit_id.required' => 'Unit bisnis wajib dipilih.',
            'employee_id.required' => 'Karyawan wajib dipilih.',
            'loan_amount.required' => 'Jumlah pinjaman wajib diisi.',
            'loan_amount.min' => 'Jumlah pinjaman minimal 1.',
            'installment_count.required' => 'Jumlah cicilan wajib diisi.',
            'installment_count.min' => 'Jumlah cicilan minimal 1 bulan.',
            'disbursed_date.required' => 'Tanggal pencairan wajib diisi.',
            'payment_coa_id.required' => 'Akun pembayaran wajib dipilih.',
        ];
    }

    public function openEmployeeLoanForm($id = null)
    {
        $this->resetValidation();
        $this->resetExcept('showModal');

        if ($id) {
            // View mode — not used for now
            $this->editMode = true;
            $this->loanId = $id;
        } else {
            $this->editMode = false;
            $buId = BusinessUnitService::resolveBusinessUnitId($this->business_unit_id);
            if ($buId) {
                $this->business_unit_id = $buId;
                $this->loan_number = EmployeeLoan::generateLoanNumber($buId);
            }
            $this->disbursed_date = now()->toDateString();
        }

        $this->showModal = true;
    }

    public function updatedBusinessUnitId($value)
    {
        if ($value) {
            $this->loan_number = EmployeeLoan::generateLoanNumber((int) $value);
        }
    }

    public function getInstallmentAmountProperty()
    {
        if ($this->loan_amount && $this->installment_count && $this->installment_count > 0) {
            return (int) ceil((int) $this->loan_amount / (int) $this->installment_count);
        }
        return 0;
    }

    public function getEmployeesProperty()
    {
        if (!$this->business_unit_id) return collect();
        return Employee::byBusinessUnit($this->business_unit_id)->active()->orderBy('name')->get();
    }

    public function getCashAccountsProperty()
    {
        return COA::where('type', 'aktiva')
            ->where('is_parent', false)
            ->where(function ($q) {
                $q->where('code', 'like', '1101%') // Kas
                    ->orWhere('code', 'like', '1103%'); // Bank
            })
            ->orderBy('code')
            ->get();
    }

    public function save()
    {
        $buId = BusinessUnitService::resolveBusinessUnitId($this->business_unit_id);
        if ($buId) {
            $this->business_unit_id = $buId;
        }

        $this->validate();

        try {
            $service = app(EmployeeLoanService::class);

            $service->createLoan([
                'business_unit_id' => $this->business_unit_id,
                'employee_id' => $this->employee_id,
                'loan_number' => $this->loan_number,
                'description' => $this->description,
                'loan_amount' => $this->loan_amount,
                'installment_count' => $this->installment_count,
                'disbursed_date' => $this->disbursed_date,
                'start_deduction_date' => $this->start_deduction_date ?: null,
                'payment_coa_id' => $this->payment_coa_id,
                'notes' => $this->notes,
            ]);

            $this->showModal = false;
            $this->dispatch('refreshEmployeeLoanList');
            $this->dispatch('alert', type: 'success', message: 'Pinjaman berhasil dibuat dan dicairkan.');
        } catch (\Exception $e) {
            $this->dispatch('alert', type: 'error', message: $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.loan.employee-loan-form', [
            'employees' => $this->employees,
            'cashAccounts' => $this->cashAccounts,
            'units' => BusinessUnitService::getAvailableUnits(),
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
