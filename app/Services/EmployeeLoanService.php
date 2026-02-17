<?php

namespace App\Services;

use App\Models\BusinessUnit;
use App\Models\COA;
use App\Models\EmployeeLoan;
use App\Models\EmployeeLoanPayment;
use App\Models\Period;
use Illuminate\Support\Facades\DB;

class EmployeeLoanService
{
    protected JournalService $journalService;

    public function __construct(JournalService $journalService)
    {
        $this->journalService = $journalService;
    }

    /**
     * Create a new employee loan and disburse (create journal).
     *
     * Journal:
     * Debit: Piutang Karyawan = loan_amount
     * Credit: Kas/Bank = loan_amount
     */
    public function createLoan(array $data): EmployeeLoan
    {
        return DB::transaction(function () use ($data) {
            $buId = $data['business_unit_id'];
            $loanAmount = (int) $data['loan_amount'];
            $installmentCount = (int) $data['installment_count'];
            $installmentAmount = (int) ceil($loanAmount / $installmentCount);

            $loan = EmployeeLoan::create([
                'business_unit_id' => $buId,
                'employee_id' => $data['employee_id'],
                'loan_number' => $data['loan_number'] ?? EmployeeLoan::generateLoanNumber($buId),
                'description' => $data['description'] ?? null,
                'loan_amount' => $loanAmount,
                'installment_count' => $installmentCount,
                'installment_amount' => $installmentAmount,
                'disbursed_date' => $data['disbursed_date'],
                'start_deduction_date' => $data['start_deduction_date'] ?? null,
                'payment_coa_id' => $data['payment_coa_id'],
                'total_paid' => 0,
                'remaining_amount' => $loanAmount,
                'status' => 'active',
                'notes' => $data['notes'] ?? null,
            ]);

            // Create disbursement journal
            $journal = $this->createDisbursementJournal($loan);
            $loan->update(['journal_master_id' => $journal->id]);

            return $loan->fresh(['employee', 'businessUnit', 'paymentCoa']);
        });
    }

    /**
     * Create journal for loan disbursement.
     * Debit: Piutang Karyawan
     * Credit: Kas/Bank (payment_coa)
     */
    protected function createDisbursementJournal(EmployeeLoan $loan)
    {
        $period = Period::current()->open()->first();
        if (!$period) {
            throw new \Exception('Tidak ada periode akuntansi aktif yang terbuka.');
        }

        $bu = BusinessUnit::findOrFail($loan->business_unit_id);
        $piutangKaryawanCoa = $bu->getCoaByKey('piutang_karyawan');
        if (!$piutangKaryawanCoa) {
            throw new \Exception('Akun Piutang Karyawan belum di-mapping untuk unit bisnis ini.');
        }

        $paymentCoa = COA::findOrFail($loan->payment_coa_id);

        return $this->journalService->createJournalEntry([
            'journal_date' => $loan->disbursed_date->toDateString(),
            'reference' => $loan->loan_number,
            'description' => 'Pencairan Pinjaman: ' . $loan->employee->name . ' - ' . $loan->loan_number,
            'id_period' => $period->id,
            'type' => 'general',
            'status' => 'posted',
            'entries' => [
                [
                    'coa_code' => $piutangKaryawanCoa->code,
                    'description' => 'Piutang Karyawan - ' . $loan->employee->name,
                    'debit' => $loan->loan_amount,
                    'credit' => 0,
                ],
                [
                    'coa_code' => $paymentCoa->code,
                    'description' => 'Pencairan Pinjaman ' . $loan->loan_number,
                    'debit' => 0,
                    'credit' => $loan->loan_amount,
                ],
            ],
        ]);
    }

    /**
     * Record a manual payment (outside payroll).
     *
     * Journal:
     * Debit: Kas/Bank = amount
     * Credit: Piutang Karyawan = amount
     */
    public function recordManualPayment(EmployeeLoan $loan, array $data): EmployeeLoanPayment
    {
        if ($loan->status !== 'active') {
            throw new \Exception('Pinjaman tidak dalam status aktif.');
        }

        $amount = (int) $data['amount'];
        if ($amount <= 0) {
            throw new \Exception('Jumlah pembayaran harus lebih dari 0.');
        }

        if ($amount > $loan->remaining_amount) {
            throw new \Exception('Jumlah pembayaran melebihi sisa pinjaman (Rp ' . number_format($loan->remaining_amount) . ').');
        }

        return DB::transaction(function () use ($loan, $data, $amount) {
            // Create journal
            $journal = $this->createManualPaymentJournal($loan, $data, $amount);

            // Record payment
            $payment = EmployeeLoanPayment::create([
                'employee_loan_id' => $loan->id,
                'payroll_period_id' => null,
                'payroll_entry_detail_id' => null,
                'payment_date' => $data['payment_date'],
                'amount' => $amount,
                'reference' => $data['reference'] ?? null,
                'journal_master_id' => $journal->id,
                'notes' => $data['notes'] ?? null,
            ]);

            // Update loan balance
            $loan->recordPayment($amount);

            return $payment;
        });
    }

    /**
     * Create journal for manual loan payment.
     * Debit: Kas/Bank
     * Credit: Piutang Karyawan
     */
    protected function createManualPaymentJournal(EmployeeLoan $loan, array $data, int $amount)
    {
        $period = Period::current()->open()->first();
        if (!$period) {
            throw new \Exception('Tidak ada periode akuntansi aktif yang terbuka.');
        }

        $bu = BusinessUnit::findOrFail($loan->business_unit_id);
        $piutangKaryawanCoa = $bu->getCoaByKey('piutang_karyawan');
        if (!$piutangKaryawanCoa) {
            throw new \Exception('Akun Piutang Karyawan belum di-mapping untuk unit bisnis ini.');
        }

        $paymentCoa = COA::findOrFail($data['payment_coa_id']);

        return $this->journalService->createJournalEntry([
            'journal_date' => $data['payment_date'],
            'reference' => $data['reference'] ?? $loan->loan_number . '-PAY',
            'description' => 'Pembayaran Pinjaman: ' . $loan->employee->name . ' - ' . $loan->loan_number,
            'id_period' => $period->id,
            'type' => 'general',
            'status' => 'posted',
            'entries' => [
                [
                    'coa_code' => $paymentCoa->code,
                    'description' => 'Terima Pembayaran Pinjaman ' . $loan->loan_number,
                    'debit' => $amount,
                    'credit' => 0,
                ],
                [
                    'coa_code' => $piutangKaryawanCoa->code,
                    'description' => 'Piutang Karyawan - ' . $loan->employee->name,
                    'debit' => 0,
                    'credit' => $amount,
                ],
            ],
        ]);
    }

    /**
     * Void a loan (only if no payments have been made).
     */
    public function voidLoan(EmployeeLoan $loan): EmployeeLoan
    {
        if ($loan->status !== 'active') {
            throw new \Exception('Hanya pinjaman aktif yang dapat dibatalkan.');
        }

        if ($loan->total_paid > 0) {
            throw new \Exception('Tidak dapat membatalkan pinjaman yang sudah memiliki pembayaran.');
        }

        return DB::transaction(function () use ($loan) {
            $loan->update(['status' => 'void']);
            return $loan->fresh();
        });
    }

    /**
     * Get active loans for an employee that should be deducted for a given period.
     *
     * @return \Illuminate\Database\Eloquent\Collection<EmployeeLoan>
     */
    public function getDeductibleLoans(int $employeeId, int $month, int $year)
    {
        return EmployeeLoan::byEmployee($employeeId)
            ->deductibleForPeriod($month, $year)
            ->get();
    }

    /**
     * Record a payroll-based loan payment.
     * Called from PayrollService after payroll is paid.
     */
    public function recordPayrollPayment(
        EmployeeLoan $loan,
        int $amount,
        int $payrollPeriodId,
        ?int $payrollEntryDetailId = null
    ): EmployeeLoanPayment {
        return DB::transaction(function () use ($loan, $amount, $payrollPeriodId, $payrollEntryDetailId) {
            $payment = EmployeeLoanPayment::create([
                'employee_loan_id' => $loan->id,
                'payroll_period_id' => $payrollPeriodId,
                'payroll_entry_detail_id' => $payrollEntryDetailId,
                'payment_date' => now()->toDateString(),
                'amount' => $amount,
                'reference' => 'PAYROLL',
                'journal_master_id' => null, // journal is handled by payroll
                'notes' => 'Potongan otomatis dari payroll',
            ]);

            $loan->recordPayment($amount);

            return $payment;
        });
    }
}
