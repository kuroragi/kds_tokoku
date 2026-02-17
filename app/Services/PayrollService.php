<?php

namespace App\Services;

use App\Models\BusinessUnit;
use App\Models\COA;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\PayrollEntry;
use App\Models\PayrollEntryDetail;
use App\Models\PayrollPeriod;
use App\Models\PayrollSetting;
use App\Models\Period;
use App\Models\Pph21TerRate;
use App\Models\SalaryComponent;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    protected JournalService $journalService;

    /**
     * Mapping setting_key to cap_key in payroll_settings.
     */
    protected static array $capMappings = [
        'bpjs_kes_company_rate' => 'bpjs_kes_cap',
        'bpjs_kes_employee_rate' => 'bpjs_kes_cap',
        'bpjs_jp_company_rate' => 'bpjs_jp_cap',
        'bpjs_jp_employee_rate' => 'bpjs_jp_cap',
    ];

    public function __construct(JournalService $journalService)
    {
        $this->journalService = $journalService;
    }

    /**
     * Calculate payroll for a given period.
     * Generates PayrollEntry + PayrollEntryDetail for each active employee.
     */
    public function calculatePayroll(PayrollPeriod $payrollPeriod): PayrollPeriod
    {
        if (!$payrollPeriod->canCalculate()) {
            throw new \Exception('Payroll tidak dapat dihitung pada status: ' . $payrollPeriod->status);
        }

        $buId = $payrollPeriod->business_unit_id;

        // Get all active salary components for this BU
        $components = SalaryComponent::byBusinessUnit($buId)->active()
            ->orderBy('sort_order')->get();

        // Get active employees with salary
        $employees = Employee::byBusinessUnit($buId)->active()
            ->whereNotNull('base_salary')
            ->where('base_salary', '>', 0)
            ->get();

        if ($employees->isEmpty()) {
            throw new \Exception('Tidak ada karyawan aktif dengan gaji pokok untuk diproses.');
        }

        // Check PPh21 setting
        $pph21Enabled = (bool) PayrollSetting::getValue($buId, 'pph21_enabled', false);

        return DB::transaction(function () use ($payrollPeriod, $employees, $components, $buId, $pph21Enabled) {
            // Remove existing entries if recalculating
            $payrollPeriod->entries()->each(function ($entry) {
                $entry->details()->delete();
                $entry->forceDelete();
            });

            foreach ($employees as $employee) {
                $this->calculateEmployeePayroll($payrollPeriod, $employee, $components, $buId, $pph21Enabled);
            }

            $payrollPeriod->status = 'calculated';
            $payrollPeriod->recalculateTotals();

            return $payrollPeriod->fresh('entries.details');
        });
    }

    /**
     * Calculate payroll for a single employee.
     */
    protected function calculateEmployeePayroll(
        PayrollPeriod $period,
        Employee $employee,
        $components,
        int $buId,
        bool $pph21Enabled
    ): PayrollEntry {
        $entry = PayrollEntry::create([
            'payroll_period_id' => $period->id,
            'employee_id' => $employee->id,
            'base_salary' => $employee->base_salary,
        ]);

        $details = [];

        foreach ($components as $component) {
            $amount = $this->calculateComponentAmount($component, $employee, $buId, $pph21Enabled);

            // Skip zero-amount auto components
            if ($amount === 0 && $component->apply_method === 'auto') {
                continue;
            }

            // Skip template components with no assignment (amount null or 0)
            if ($component->apply_method === 'template' && $amount === 0) {
                // Check if there's an explicit assignment with 0 — include it; otherwise skip
                $hasAssignment = $employee->salaryComponents()
                    ->where('salary_component_id', $component->id)->exists()
                    || ($employee->position_id && \App\Models\PositionSalaryComponent::where('position_id', $employee->position_id)
                        ->where('salary_component_id', $component->id)->exists());

                if (!$hasAssignment) {
                    continue;
                }
            }

            // Skip manual components (they're added manually per payroll)
            if ($component->apply_method === 'manual') {
                continue;
            }

            // Skip PPh21 if disabled
            if ($component->category === 'pph21' && !$pph21Enabled) {
                continue;
            }

            // Skip loan deduction component (handled separately below)
            if ($component->category === 'pinjaman') {
                continue;
            }

            $details[] = PayrollEntryDetail::create([
                'payroll_entry_id' => $entry->id,
                'salary_component_id' => $component->id,
                'component_name' => $component->name,
                'type' => $component->type,
                'category' => $component->category,
                'amount' => $amount,
                'is_auto_calculated' => true,
            ]);
        }

        // Preserve existing manual entries when recalculating
        // (manual items are added separately and should not be removed)

        // ==================== LOAN DEDUCTIONS ====================
        $this->addLoanDeductions($entry, $employee, $period, $buId);

        // ==================== STORE PPh21 RATE ====================
        if ($pph21Enabled && $employee->ptkp_status) {
            $grossIncome = $employee->base_salary ?? 0;
            $templateComponents = SalaryComponent::byBusinessUnit($buId)
                ->active()->where('type', 'earning')
                ->where('apply_method', 'template')->get();
            foreach ($templateComponents as $comp) {
                $grossIncome += $employee->getEffectiveSalaryAmount($comp) ?? 0;
            }
            $category = Pph21TerRate::getCategoryForPtkp($employee->ptkp_status);
            $entry->pph21_rate = Pph21TerRate::getRate($category, $grossIncome);
        }

        $entry->recalculateFromDetails();

        return $entry;
    }

    /**
     * Add loan deductions for an employee.
     */
    protected function addLoanDeductions(
        PayrollEntry $entry,
        Employee $employee,
        PayrollPeriod $period,
        int $buId
    ): void {
        $loans = EmployeeLoan::byEmployee($employee->id)
            ->deductibleForPeriod($period->month, $period->year)
            ->get();

        if ($loans->isEmpty()) return;

        // Find or use POT-PINJAMAN component
        $loanComponent = SalaryComponent::byBusinessUnit($buId)
            ->where('code', 'POT-PINJAMAN')->first();

        foreach ($loans as $loan) {
            $deductionAmount = $loan->getDeductionAmount();
            if ($deductionAmount <= 0) continue;

            PayrollEntryDetail::create([
                'payroll_entry_id' => $entry->id,
                'salary_component_id' => $loanComponent?->id,
                'component_name' => 'Potongan Pinjaman ' . $loan->loan_number,
                'type' => 'deduction',
                'category' => 'pinjaman',
                'amount' => $deductionAmount,
                'is_auto_calculated' => true,
                'notes' => 'Cicilan ' . $loan->loan_number
                    . ' (sisa: Rp ' . number_format($loan->remaining_amount) . ')',
            ]);
        }
    }

    /**
     * Calculate a component amount for a given employee.
     */
    protected function calculateComponentAmount(
        SalaryComponent $component,
        Employee $employee,
        int $buId,
        bool $pph21Enabled
    ): int {
        // PPh21 special handling
        if ($component->category === 'pph21') {
            if (!$pph21Enabled) {
                return 0;
            }
            return $this->calculatePph21($employee, $buId);
        }

        return match ($component->calculation_type) {
            'employee_field' => $this->calculateFromEmployeeField($component, $employee),
            'percentage' => $this->calculatePercentage($component, $employee, $buId),
            'fixed' => $this->calculateFixed($component, $employee),
            default => 0,
        };
    }

    /**
     * Calculate from employee model field (e.g., base_salary for Gaji Pokok).
     */
    protected function calculateFromEmployeeField(SalaryComponent $component, Employee $employee): int
    {
        $field = $component->employee_field_name;
        if (!$field || !isset($employee->{$field})) {
            return 0;
        }
        return (int) $employee->{$field};
    }

    /**
     * Calculate percentage-based component (BPJS).
     * Reads rate from payroll_settings and applies cap if applicable.
     */
    protected function calculatePercentage(SalaryComponent $component, Employee $employee, int $buId): int
    {
        $rate = 0;

        // Get rate from settings if setting_key is set
        if ($component->setting_key) {
            $rate = (float) PayrollSetting::getValue($buId, $component->setting_key, 0);
        } elseif ($component->default_amount) {
            // Fallback to default_amount as rate (stored as rate * 100, e.g., 250 for 2.5%)
            $rate = $component->default_amount / 100;
        }

        if ($rate <= 0) {
            return 0;
        }

        // Determine base amount
        $base = $employee->base_salary ?? 0;

        // Apply cap if exists for this setting
        if ($component->setting_key && isset(self::$capMappings[$component->setting_key])) {
            $capKey = self::$capMappings[$component->setting_key];
            $cap = PayrollSetting::getValue($buId, $capKey);
            if ($cap && $base > $cap) {
                $base = $cap;
            }
        }

        return (int) round($base * $rate / 100);
    }

    /**
     * Calculate fixed-amount component.
     * Priority: employee override > position template > default.
     */
    protected function calculateFixed(SalaryComponent $component, Employee $employee): int
    {
        return $employee->getEffectiveSalaryAmount($component) ?? 0;
    }

    /**
     * Calculate PPh21 using TER method (foundation).
     */
    protected function calculatePph21(Employee $employee, int $buId): int
    {
        if (!$employee->ptkp_status) {
            return 0;
        }

        // Calculate gross monthly income (gaji pokok + tunjangan)
        // For simplicity, we use base_salary + all earning components
        $grossIncome = $employee->base_salary ?? 0;

        // Add template earnings
        $templateComponents = SalaryComponent::byBusinessUnit($buId)
            ->active()
            ->where('type', 'earning')
            ->where('apply_method', 'template')
            ->get();

        foreach ($templateComponents as $comp) {
            $grossIncome += $employee->getEffectiveSalaryAmount($comp) ?? 0;
        }

        return Pph21TerRate::calculateTax($employee->ptkp_status, $grossIncome);
    }

    /**
     * Add a manual item to a payroll entry.
     */
    public function addManualItem(
        PayrollEntry $entry,
        ?int $componentId,
        string $componentName,
        string $type,
        string $category,
        int $amount,
        ?string $notes = null
    ): PayrollEntryDetail {
        $period = $entry->payrollPeriod;
        if (!in_array($period->status, ['draft', 'calculated'])) {
            throw new \Exception('Tidak dapat menambah item pada payroll dengan status: ' . $period->status);
        }

        $detail = PayrollEntryDetail::create([
            'payroll_entry_id' => $entry->id,
            'salary_component_id' => $componentId,
            'component_name' => $componentName,
            'type' => $type,
            'category' => $category,
            'amount' => $amount,
            'is_auto_calculated' => false,
            'notes' => $notes,
        ]);

        $entry->recalculateFromDetails();
        $period->recalculateTotals();

        return $detail;
    }

    /**
     * Remove a manual item from a payroll entry.
     */
    public function removeManualItem(PayrollEntryDetail $detail): void
    {
        $entry = $detail->payrollEntry;
        $period = $entry->payrollPeriod;

        if (!in_array($period->status, ['draft', 'calculated'])) {
            throw new \Exception('Tidak dapat menghapus item pada payroll dengan status: ' . $period->status);
        }

        if ($detail->is_auto_calculated) {
            throw new \Exception('Tidak dapat menghapus item yang dihitung otomatis.');
        }

        $detail->delete();
        $entry->recalculateFromDetails();
        $period->recalculateTotals();
    }

    /**
     * Approve a payroll period.
     */
    public function approvePayroll(PayrollPeriod $period): PayrollPeriod
    {
        if (!$period->canApprove()) {
            throw new \Exception('Payroll tidak dapat disetujui pada status: ' . $period->status);
        }

        $period->update(['status' => 'approved']);
        return $period->fresh();
    }

    /**
     * Pay payroll and create journal entry.
     *
     * Journal:
     * Debit: Beban Gaji = SUM(total_earnings + total_benefits)
     * Credit: Kas/Bank = SUM(net_salary)
     * Credit: Hutang Gaji = SUM(total_benefits + total_deductions - pph21_amount - loan_deductions)
     * Credit: Hutang Pajak = SUM(pph21_amount) — only if > 0
     * Credit: Piutang Karyawan = SUM(loan_deductions) — only if > 0
     */
    public function payPayroll(PayrollPeriod $period, int $paymentCoaId): PayrollPeriod
    {
        if (!$period->canPay()) {
            throw new \Exception('Payroll tidak dapat dibayar pada status: ' . $period->status);
        }

        return DB::transaction(function () use ($period, $paymentCoaId) {
            $bu = BusinessUnit::findOrFail($period->business_unit_id);

            // Resolve COA accounts
            $bebanGajiCoa = $bu->getCoaByKey('beban_gaji');
            if (!$bebanGajiCoa) {
                throw new \Exception('Akun Beban Gaji belum di-mapping untuk unit bisnis ini.');
            }

            $hutangGajiCoa = $bu->getCoaByKey('hutang_gaji');
            if (!$hutangGajiCoa) {
                throw new \Exception('Akun Hutang Gaji belum di-mapping untuk unit bisnis ini.');
            }

            $paymentCoa = COA::findOrFail($paymentCoaId);

            // Calculate loan deductions total
            $totalLoanDeductions = (int) PayrollEntryDetail::whereHas('payrollEntry', function ($q) use ($period) {
                $q->where('payroll_period_id', $period->id);
            })->where('category', 'pinjaman')->sum('amount');

            // Calculate totals
            $totalDebit = $period->total_earnings + $period->total_benefits;
            $totalNet = $period->total_net;
            $totalHutangGaji = $period->total_benefits + $period->total_deductions
                - $period->total_tax - $totalLoanDeductions;
            $totalPph21 = $period->total_tax;

            // Build journal entries
            $entries = [];

            // Debit: Beban Gaji
            $entries[] = [
                'coa_code' => $bebanGajiCoa->code,
                'description' => 'Beban Gaji ' . $period->name,
                'debit' => $totalDebit,
                'credit' => 0,
            ];

            // Credit: Kas/Bank (payment)
            $entries[] = [
                'coa_code' => $paymentCoa->code,
                'description' => 'Pembayaran Gaji ' . $period->name,
                'debit' => 0,
                'credit' => $totalNet,
            ];

            // Credit: Hutang Gaji (BPJS + deductions - PPh21 - loan)
            if ($totalHutangGaji > 0) {
                $entries[] = [
                    'coa_code' => $hutangGajiCoa->code,
                    'description' => 'Hutang BPJS & Potongan ' . $period->name,
                    'debit' => 0,
                    'credit' => $totalHutangGaji,
                ];
            }

            // Credit: Hutang Pajak (PPh21)
            if ($totalPph21 > 0) {
                $hutangPajakCoa = $bu->getCoaByKey('hutang_pajak');
                if (!$hutangPajakCoa) {
                    throw new \Exception('Akun Hutang Pajak belum di-mapping untuk unit bisnis ini.');
                }

                $entries[] = [
                    'coa_code' => $hutangPajakCoa->code,
                    'description' => 'PPh 21 ' . $period->name,
                    'debit' => 0,
                    'credit' => $totalPph21,
                ];
            }

            // Credit: Piutang Karyawan (loan deductions)
            if ($totalLoanDeductions > 0) {
                $piutangKaryawanCoa = $bu->getCoaByKey('piutang_karyawan');
                if (!$piutangKaryawanCoa) {
                    throw new \Exception('Akun Piutang Karyawan belum di-mapping untuk unit bisnis ini.');
                }

                $entries[] = [
                    'coa_code' => $piutangKaryawanCoa->code,
                    'description' => 'Potongan Pinjaman Karyawan ' . $period->name,
                    'debit' => 0,
                    'credit' => $totalLoanDeductions,
                ];
            }

            // Get accounting period
            $accountingPeriod = Period::current()->open()->first();
            if (!$accountingPeriod) {
                throw new \Exception('Tidak ada periode akuntansi aktif yang terbuka.');
            }

            // Create journal
            $journalMaster = $this->journalService->createJournalEntry([
                'journal_date' => now()->toDateString(),
                'reference' => 'PAYROLL-' . $period->year . str_pad($period->month, 2, '0', STR_PAD_LEFT),
                'description' => 'Pembayaran Gaji ' . $period->name,
                'id_period' => $accountingPeriod->id,
                'type' => 'general',
                'status' => 'posted',
                'entries' => $entries,
            ]);

            $period->update([
                'status' => 'paid',
                'payment_coa_id' => $paymentCoaId,
                'journal_master_id' => $journalMaster->id,
                'paid_date' => now()->toDateString(),
            ]);

            // ==================== RECORD LOAN PAYMENTS ====================
            if ($totalLoanDeductions > 0) {
                $this->recordLoanPaymentsFromPayroll($period);
            }

            return $period->fresh();
        });
    }

    /**
     * Record loan payments from a paid payroll period.
     */
    protected function recordLoanPaymentsFromPayroll(PayrollPeriod $period): void
    {
        $loanService = app(EmployeeLoanService::class);

        $loanDetails = PayrollEntryDetail::whereHas('payrollEntry', function ($q) use ($period) {
            $q->where('payroll_period_id', $period->id);
        })->where('category', 'pinjaman')
            ->with('payrollEntry')
            ->get();

        foreach ($loanDetails as $detail) {
            // Find the loan by matching employee and notes (which contains loan_number)
            $employeeId = $detail->payrollEntry->employee_id;

            // Extract loan number from notes
            preg_match('/Cicilan (LOAN-[\w-]+)/', $detail->notes ?? '', $matches);
            $loanNumber = $matches[1] ?? null;

            if (!$loanNumber) continue;

            $loan = EmployeeLoan::where('employee_id', $employeeId)
                ->where('loan_number', $loanNumber)
                ->where('status', 'active')
                ->first();

            if (!$loan) continue;

            $loanService->recordPayrollPayment(
                $loan,
                $detail->amount,
                $period->id,
                $detail->id
            );
        }
    }

    /**
     * Void a payroll period.
     */
    public function voidPayroll(PayrollPeriod $period): PayrollPeriod
    {
        if (!$period->canVoid()) {
            throw new \Exception('Payroll tidak dapat dibatalkan pada status: ' . $period->status);
        }

        return DB::transaction(function () use ($period) {
            // If there's a journal, we don't delete it — just mark payroll as void
            $period->update(['status' => 'void']);
            return $period->fresh();
        });
    }
}
