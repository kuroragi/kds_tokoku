<?php

namespace Tests\Feature;

use App\Livewire\Loan\EmployeeLoanDetail;
use App\Livewire\Loan\EmployeeLoanForm;
use App\Livewire\Loan\EmployeeLoanList;
use App\Livewire\Payroll\PayrollReportBpjs;
use App\Livewire\Payroll\PayrollReportEmployee;
use App\Livewire\Payroll\PayrollReportRecap;
use App\Models\BusinessUnit;
use App\Models\BusinessUnitCoaMapping;
use App\Models\COA;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\EmployeeLoanPayment;
use App\Models\Journal;
use App\Models\PayrollEntry;
use App\Models\PayrollEntryDetail;
use App\Models\PayrollPeriod;
use App\Models\PayrollSetting;
use App\Models\Period;
use App\Models\Pph21TerRate;
use App\Models\Position;
use App\Models\SalaryComponent;
use App\Models\User;
use App\Services\EmployeeLoanService;
use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class Phase2Test extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected BusinessUnit $unit;
    protected Period $period;
    protected Position $position;
    protected Employee $employee;
    protected COA $cashCoa;
    protected COA $bebanGajiCoa;
    protected COA $hutangGajiCoa;
    protected COA $hutangPajakCoa;
    protected COA $piutangKaryawanCoa;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'superadmin']);

        $this->user = User::withoutEvents(fn() => User::factory()->create());
        $this->user->assignRole('superadmin');
        $this->actingAs($this->user);

        $this->unit = BusinessUnit::withoutEvents(fn() => BusinessUnit::create([
            'code' => 'UNT-001', 'name' => 'Test Unit', 'is_active' => true,
        ]));

        $now = Carbon::now();
        $this->period = Period::create([
            'code' => $now->format('Ym'),
            'name' => $now->translatedFormat('F') . ' ' . $now->year,
            'start_date' => $now->copy()->startOfMonth()->format('Y-m-d'),
            'end_date' => $now->copy()->endOfMonth()->format('Y-m-d'),
            'year' => $now->year,
            'month' => $now->month,
            'is_active' => true,
            'is_closed' => false,
        ]);

        // COAs
        $this->cashCoa = COA::create([
            'code' => '1101', 'name' => 'Kas', 'type' => 'aktiva',
            'level' => 2, 'order' => 1, 'is_active' => true, 'is_leaf_account' => true,
        ]);
        $this->bebanGajiCoa = COA::create([
            'code' => '5201', 'name' => 'Beban Gaji', 'type' => 'beban',
            'level' => 2, 'order' => 1, 'is_active' => true, 'is_leaf_account' => true,
        ]);
        $this->hutangGajiCoa = COA::create([
            'code' => '2201', 'name' => 'Hutang Gaji', 'type' => 'pasiva',
            'level' => 2, 'order' => 1, 'is_active' => true, 'is_leaf_account' => true,
        ]);
        $this->hutangPajakCoa = COA::create([
            'code' => '2202', 'name' => 'Hutang Pajak', 'type' => 'pasiva',
            'level' => 2, 'order' => 2, 'is_active' => true, 'is_leaf_account' => true,
        ]);
        $this->piutangKaryawanCoa = COA::create([
            'code' => '1102', 'name' => 'Piutang Karyawan', 'type' => 'aktiva',
            'level' => 2, 'order' => 2, 'is_active' => true, 'is_leaf_account' => true,
        ]);

        // COA Mappings
        BusinessUnitCoaMapping::create([
            'business_unit_id' => $this->unit->id,
            'account_key' => 'beban_gaji',
            'label' => 'Beban Gaji',
            'coa_id' => $this->bebanGajiCoa->id,
        ]);
        BusinessUnitCoaMapping::create([
            'business_unit_id' => $this->unit->id,
            'account_key' => 'hutang_gaji',
            'label' => 'Hutang Gaji',
            'coa_id' => $this->hutangGajiCoa->id,
        ]);
        BusinessUnitCoaMapping::create([
            'business_unit_id' => $this->unit->id,
            'account_key' => 'hutang_pajak',
            'label' => 'Hutang Pajak',
            'coa_id' => $this->hutangPajakCoa->id,
        ]);
        BusinessUnitCoaMapping::create([
            'business_unit_id' => $this->unit->id,
            'account_key' => 'piutang_karyawan',
            'label' => 'Piutang Karyawan',
            'coa_id' => $this->piutangKaryawanCoa->id,
        ]);

        // Position
        $this->position = Position::withoutEvents(fn() => Position::create([
            'business_unit_id' => $this->unit->id,
            'code' => 'STF',
            'name' => 'Staff',
            'is_active' => true,
        ]));

        // Employee
        $this->employee = Employee::withoutEvents(fn() => Employee::create([
            'business_unit_id' => $this->unit->id,
            'position_id' => $this->position->id,
            'code' => 'EMP-001',
            'name' => 'John Doe',
            'base_salary' => 5000000,
            'ptkp_status' => 'TK/0',
            'is_active' => true,
        ]));
    }

    protected function seedComponents(): void
    {
        SalaryComponent::seedDefaultsForBusinessUnit($this->unit->id);
    }

    protected function seedSettings(): void
    {
        PayrollSetting::seedDefaultsForBusinessUnit($this->unit->id);
    }

    protected function createPayrollPeriod(array $overrides = []): PayrollPeriod
    {
        $month = $overrides['month'] ?? now()->month;
        $year = $overrides['year'] ?? now()->year;
        $date = Carbon::create($year, $month, 1);

        return PayrollPeriod::withoutEvents(fn() => PayrollPeriod::create(array_merge([
            'business_unit_id' => $this->unit->id,
            'month' => $month,
            'year' => $year,
            'name' => 'Gaji ' . $date->translatedFormat('F') . ' ' . $year,
            'start_date' => $date->copy()->startOfMonth()->format('Y-m-d'),
            'end_date' => $date->copy()->endOfMonth()->format('Y-m-d'),
            'status' => 'draft',
        ], $overrides)));
    }

    /**
     * Helper: create an employee loan.
     */
    protected function createLoan(array $overrides = []): EmployeeLoan
    {
        return EmployeeLoan::withoutEvents(fn() => EmployeeLoan::create(array_merge([
            'business_unit_id' => $this->unit->id,
            'employee_id' => $this->employee->id,
            'loan_number' => 'LOAN-' . now()->format('Ym') . '-0001',
            'loan_amount' => 3000000,
            'installment_count' => 3,
            'installment_amount' => 1000000,
            'disbursed_date' => now()->subDays(5)->toDateString(),
            'start_deduction_date' => null,
            'payment_coa_id' => $this->cashCoa->id,
            'total_paid' => 0,
            'remaining_amount' => 3000000,
            'status' => 'active',
        ], $overrides)));
    }

    // ==================== PAGE ACCESS TESTS ====================

    /** @test */
    public function employee_loan_index_page_is_accessible()
    {
        $response = $this->get(route('employee-loan.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function employee_loan_detail_page_is_accessible()
    {
        $loan = $this->createLoan();
        $response = $this->get(route('employee-loan.detail', $loan));
        $response->assertStatus(200);
    }

    /** @test */
    public function payroll_report_recap_page_is_accessible()
    {
        $response = $this->get(route('payroll-report.recap'));
        $response->assertStatus(200);
    }

    /** @test */
    public function payroll_report_employee_page_is_accessible()
    {
        $response = $this->get(route('payroll-report.employee'));
        $response->assertStatus(200);
    }

    /** @test */
    public function payroll_report_bpjs_page_is_accessible()
    {
        $response = $this->get(route('payroll-report.bpjs'));
        $response->assertStatus(200);
    }

    /** @test */
    public function guest_cannot_access_loan_pages()
    {
        auth()->logout();
        $this->get(route('employee-loan.index'))->assertRedirect(route('login'));
    }

    /** @test */
    public function guest_cannot_access_payroll_report_pages()
    {
        auth()->logout();
        $this->get(route('payroll-report.recap'))->assertRedirect(route('login'));
        $this->get(route('payroll-report.employee'))->assertRedirect(route('login'));
        $this->get(route('payroll-report.bpjs'))->assertRedirect(route('login'));
    }

    // ==================== EMPLOYEE LOAN MODEL TESTS ====================

    /** @test */
    public function employee_loan_model_has_correct_relationships()
    {
        $loan = $this->createLoan();

        $this->assertInstanceOf(Employee::class, $loan->employee);
        $this->assertInstanceOf(BusinessUnit::class, $loan->businessUnit);
        $this->assertInstanceOf(COA::class, $loan->paymentCoa);
    }

    /** @test */
    public function employee_loan_computed_attributes()
    {
        $loan = $this->createLoan([
            'loan_amount' => 3000000,
            'installment_count' => 3,
            'installment_amount' => 1000000,
            'total_paid' => 1000000,
            'remaining_amount' => 2000000,
            'status' => 'active',
        ]);

        $this->assertTrue($loan->is_active);
        $this->assertFalse($loan->is_paid_off);
        $this->assertEquals(2, $loan->remaining_installments);
        $this->assertEqualsWithDelta(33.3, $loan->progress_percent, 0.1);
    }

    /** @test */
    public function employee_loan_deduction_amount()
    {
        $loan = $this->createLoan([
            'installment_amount' => 1000000,
            'remaining_amount' => 500000,
            'status' => 'active',
        ]);

        // Should return min(installment_amount, remaining_amount)
        $this->assertEquals(500000, $loan->getDeductionAmount());

        $loan->remaining_amount = 2000000;
        $this->assertEquals(1000000, $loan->getDeductionAmount());
    }

    /** @test */
    public function employee_loan_deduction_amount_zero_for_inactive()
    {
        $loan = $this->createLoan(['status' => 'paid_off']);
        $this->assertEquals(0, $loan->getDeductionAmount());

        $loan2 = $this->createLoan([
            'loan_number' => 'LOAN-TEST-0002',
            'status' => 'void',
        ]);
        $this->assertEquals(0, $loan2->getDeductionAmount());
    }

    /** @test */
    public function employee_loan_should_deduct_for_period()
    {
        $now = now();
        $loan = $this->createLoan([
            'disbursed_date' => $now->copy()->subMonth(1)->toDateString(),
            'start_deduction_date' => null,
            'status' => 'active',
        ]);

        // Current period: should deduct (disbursed last month)
        $this->assertTrue($loan->shouldDeductForPeriod($now->month, $now->year));
    }

    /** @test */
    public function employee_loan_should_not_deduct_before_start_date()
    {
        $now = now();
        $loan = $this->createLoan([
            'disbursed_date' => $now->copy()->subMonths(2)->toDateString(),
            'start_deduction_date' => $now->copy()->addMonth()->startOfMonth()->toDateString(),
            'status' => 'active',
        ]);

        // Current period: should NOT deduct (start_deduction_date is next month)
        $this->assertFalse($loan->shouldDeductForPeriod($now->month, $now->year));

        // Next month: should deduct
        $nextMonth = $now->copy()->addMonth();
        $this->assertTrue($loan->shouldDeductForPeriod($nextMonth->month, $nextMonth->year));
    }

    /** @test */
    public function employee_loan_record_payment_updates_balance()
    {
        $loan = $this->createLoan([
            'loan_amount' => 3000000,
            'total_paid' => 0,
            'remaining_amount' => 3000000,
            'status' => 'active',
        ]);

        $loan->recordPayment(1000000);
        $this->assertEquals(1000000, $loan->total_paid);
        $this->assertEquals(2000000, $loan->remaining_amount);
        $this->assertEquals('active', $loan->status);

        $loan->recordPayment(2000000);
        $this->assertEquals(3000000, $loan->total_paid);
        $this->assertEquals(0, $loan->remaining_amount);
        $this->assertEquals('paid_off', $loan->status);
    }

    /** @test */
    public function employee_loan_scopes_work()
    {
        $activeLoan = $this->createLoan(['status' => 'active']);
        $paidLoan = $this->createLoan([
            'loan_number' => 'LOAN-TEST-0002',
            'status' => 'paid_off',
        ]);

        $this->assertEquals(1, EmployeeLoan::active()->count());
        $this->assertEquals(1, EmployeeLoan::byBusinessUnit($this->unit->id)->active()->count());
        $this->assertEquals(2, EmployeeLoan::byEmployee($this->employee->id)->count());
        $this->assertEquals(1, EmployeeLoan::byStatus('paid_off')->count());
    }

    /** @test */
    public function employee_loan_deductible_for_period_scope()
    {
        $now = now();
        $this->createLoan([
            'disbursed_date' => $now->copy()->subDays(5)->toDateString(),
            'status' => 'active',
            'remaining_amount' => 1000000,
        ]);

        $deductible = EmployeeLoan::deductibleForPeriod($now->month, $now->year)->get();
        $this->assertCount(1, $deductible);
    }

    /** @test */
    public function employee_loan_generate_loan_number()
    {
        $number = EmployeeLoan::generateLoanNumber($this->unit->id);
        $prefix = 'LOAN-' . now()->format('Ym');
        $this->assertStringStartsWith($prefix, $number);
        $this->assertStringEndsWith('-0001', $number);

        // Create a loan and check next number
        $this->createLoan(['loan_number' => $number]);
        $nextNumber = EmployeeLoan::generateLoanNumber($this->unit->id);
        $this->assertStringEndsWith('-0002', $nextNumber);
    }

    // ==================== EMPLOYEE LOAN PAYMENT MODEL TESTS ====================

    /** @test */
    public function employee_loan_payment_from_payroll()
    {
        $loan = $this->createLoan();
        $period = $this->createPayrollPeriod();

        $payment = EmployeeLoanPayment::create([
            'employee_loan_id' => $loan->id,
            'payroll_period_id' => $period->id,
            'payroll_entry_detail_id' => null,
            'payment_date' => now()->toDateString(),
            'amount' => 1000000,
            'reference' => 'PAYROLL',
        ]);

        $this->assertTrue($payment->is_from_payroll);
        $this->assertInstanceOf(EmployeeLoan::class, $payment->loan);
    }

    /** @test */
    public function employee_loan_payment_manual()
    {
        $loan = $this->createLoan();

        $payment = EmployeeLoanPayment::create([
            'employee_loan_id' => $loan->id,
            'payroll_period_id' => null,
            'payment_date' => now()->toDateString(),
            'amount' => 500000,
            'reference' => 'CASH',
        ]);

        $this->assertFalse($payment->is_from_payroll);
    }

    // ==================== EMPLOYEE LOAN SERVICE TESTS ====================

    /** @test */
    public function loan_service_can_create_loan_with_journal()
    {
        $service = app(EmployeeLoanService::class);

        $loan = $service->createLoan([
            'business_unit_id' => $this->unit->id,
            'employee_id' => $this->employee->id,
            'loan_amount' => 6000000,
            'installment_count' => 6,
            'disbursed_date' => now()->toDateString(),
            'payment_coa_id' => $this->cashCoa->id,
            'description' => 'Pinjaman test',
        ]);

        $this->assertInstanceOf(EmployeeLoan::class, $loan);
        $this->assertEquals('active', $loan->status);
        $this->assertEquals(6000000, $loan->loan_amount);
        $this->assertEquals(1000000, $loan->installment_amount);
        $this->assertEquals(6, $loan->installment_count);
        $this->assertEquals(0, $loan->total_paid);
        $this->assertEquals(6000000, $loan->remaining_amount);
        $this->assertNotNull($loan->journal_master_id);

        // Verify disbursement journal
        $totalDebit = Journal::where('id_journal_master', $loan->journal_master_id)->sum('debit');
        $totalCredit = Journal::where('id_journal_master', $loan->journal_master_id)->sum('credit');
        $this->assertEquals($totalDebit, $totalCredit);
        $this->assertEquals(6000000, $totalDebit);

        // Verify journal lines: Debit Piutang Karyawan, Credit Kas
        $debitEntry = Journal::where('id_journal_master', $loan->journal_master_id)
            ->where('debit', '>', 0)->first();
        $creditEntry = Journal::where('id_journal_master', $loan->journal_master_id)
            ->where('credit', '>', 0)->first();

        $this->assertEquals($this->piutangKaryawanCoa->id, $debitEntry->id_coa);
        $this->assertEquals($this->cashCoa->id, $creditEntry->id_coa);
    }

    /** @test */
    public function loan_service_calculates_installment_amount_with_ceiling()
    {
        $service = app(EmployeeLoanService::class);

        $loan = $service->createLoan([
            'business_unit_id' => $this->unit->id,
            'employee_id' => $this->employee->id,
            'loan_amount' => 1000000,
            'installment_count' => 3,
            'disbursed_date' => now()->toDateString(),
            'payment_coa_id' => $this->cashCoa->id,
        ]);

        // ceil(1000000 / 3) = 333334
        $this->assertEquals(333334, $loan->installment_amount);
    }

    /** @test */
    public function loan_service_auto_generates_loan_number()
    {
        $service = app(EmployeeLoanService::class);

        $loan = $service->createLoan([
            'business_unit_id' => $this->unit->id,
            'employee_id' => $this->employee->id,
            'loan_amount' => 1000000,
            'installment_count' => 2,
            'disbursed_date' => now()->toDateString(),
            'payment_coa_id' => $this->cashCoa->id,
        ]);

        $this->assertStringStartsWith('LOAN-', $loan->loan_number);
    }

    /** @test */
    public function loan_service_can_record_manual_payment_with_journal()
    {
        $service = app(EmployeeLoanService::class);

        $loan = $service->createLoan([
            'business_unit_id' => $this->unit->id,
            'employee_id' => $this->employee->id,
            'loan_amount' => 3000000,
            'installment_count' => 3,
            'disbursed_date' => now()->toDateString(),
            'payment_coa_id' => $this->cashCoa->id,
        ]);

        $payment = $service->recordManualPayment($loan, [
            'amount' => 1000000,
            'payment_date' => now()->toDateString(),
            'payment_coa_id' => $this->cashCoa->id,
            'reference' => 'PAY-001',
        ]);

        $this->assertInstanceOf(EmployeeLoanPayment::class, $payment);
        $this->assertEquals(1000000, $payment->amount);
        $this->assertNotNull($payment->journal_master_id);

        $loan->refresh();
        $this->assertEquals(1000000, $loan->total_paid);
        $this->assertEquals(2000000, $loan->remaining_amount);
        $this->assertEquals('active', $loan->status);

        // Verify payment journal: Debit Kas, Credit Piutang Karyawan
        $debitEntry = Journal::where('id_journal_master', $payment->journal_master_id)
            ->where('debit', '>', 0)->first();
        $creditEntry = Journal::where('id_journal_master', $payment->journal_master_id)
            ->where('credit', '>', 0)->first();

        $this->assertEquals($this->cashCoa->id, $debitEntry->id_coa);
        $this->assertEquals($this->piutangKaryawanCoa->id, $creditEntry->id_coa);
    }

    /** @test */
    public function loan_service_manual_payment_marks_paid_off()
    {
        $service = app(EmployeeLoanService::class);

        $loan = $service->createLoan([
            'business_unit_id' => $this->unit->id,
            'employee_id' => $this->employee->id,
            'loan_amount' => 1000000,
            'installment_count' => 1,
            'disbursed_date' => now()->toDateString(),
            'payment_coa_id' => $this->cashCoa->id,
        ]);

        $service->recordManualPayment($loan, [
            'amount' => 1000000,
            'payment_date' => now()->toDateString(),
            'payment_coa_id' => $this->cashCoa->id,
        ]);

        $loan->refresh();
        $this->assertEquals('paid_off', $loan->status);
        $this->assertEquals(0, $loan->remaining_amount);
    }

    /** @test */
    public function loan_service_rejects_overpayment()
    {
        $service = app(EmployeeLoanService::class);

        $loan = $service->createLoan([
            'business_unit_id' => $this->unit->id,
            'employee_id' => $this->employee->id,
            'loan_amount' => 1000000,
            'installment_count' => 2,
            'disbursed_date' => now()->toDateString(),
            'payment_coa_id' => $this->cashCoa->id,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('melebihi sisa pinjaman');

        $service->recordManualPayment($loan, [
            'amount' => 2000000,
            'payment_date' => now()->toDateString(),
            'payment_coa_id' => $this->cashCoa->id,
        ]);
    }

    /** @test */
    public function loan_service_rejects_payment_on_inactive_loan()
    {
        $loan = $this->createLoan(['status' => 'paid_off']);

        $service = app(EmployeeLoanService::class);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('tidak dalam status aktif');

        $service->recordManualPayment($loan, [
            'amount' => 100000,
            'payment_date' => now()->toDateString(),
            'payment_coa_id' => $this->cashCoa->id,
        ]);
    }

    /** @test */
    public function loan_service_rejects_zero_payment()
    {
        $service = app(EmployeeLoanService::class);

        $loan = $service->createLoan([
            'business_unit_id' => $this->unit->id,
            'employee_id' => $this->employee->id,
            'loan_amount' => 1000000,
            'installment_count' => 2,
            'disbursed_date' => now()->toDateString(),
            'payment_coa_id' => $this->cashCoa->id,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('lebih dari 0');

        $service->recordManualPayment($loan, [
            'amount' => 0,
            'payment_date' => now()->toDateString(),
            'payment_coa_id' => $this->cashCoa->id,
        ]);
    }

    /** @test */
    public function loan_service_can_void_loan_without_payments()
    {
        $loan = $this->createLoan();

        $service = app(EmployeeLoanService::class);
        $result = $service->voidLoan($loan);

        $this->assertEquals('void', $result->status);
    }

    /** @test */
    public function loan_service_cannot_void_loan_with_payments()
    {
        $loan = $this->createLoan([
            'total_paid' => 1000000,
            'remaining_amount' => 2000000,
        ]);

        $service = app(EmployeeLoanService::class);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('sudah memiliki pembayaran');

        $service->voidLoan($loan);
    }

    /** @test */
    public function loan_service_cannot_void_paid_off_loan()
    {
        $loan = $this->createLoan(['status' => 'paid_off']);

        $service = app(EmployeeLoanService::class);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('pinjaman aktif');

        $service->voidLoan($loan);
    }

    /** @test */
    public function loan_service_get_deductible_loans()
    {
        $now = now();

        // Deductible loan
        $this->createLoan([
            'disbursed_date' => $now->copy()->subDays(5)->toDateString(),
            'status' => 'active',
            'remaining_amount' => 1000000,
        ]);

        // Paid off loan — not deductible
        $this->createLoan([
            'loan_number' => 'LOAN-TEST-0002',
            'disbursed_date' => $now->copy()->subDays(5)->toDateString(),
            'status' => 'paid_off',
            'remaining_amount' => 0,
        ]);

        $service = app(EmployeeLoanService::class);
        $loans = $service->getDeductibleLoans($this->employee->id, $now->month, $now->year);

        $this->assertCount(1, $loans);
    }

    /** @test */
    public function loan_service_payroll_payment()
    {
        $loan = $this->createLoan([
            'loan_amount' => 3000000,
            'remaining_amount' => 3000000,
        ]);

        $period = $this->createPayrollPeriod();

        $service = app(EmployeeLoanService::class);
        $payment = $service->recordPayrollPayment($loan, 1000000, $period->id, null);

        $this->assertEquals(1000000, $payment->amount);
        $this->assertEquals($period->id, $payment->payroll_period_id);
        $this->assertEquals('PAYROLL', $payment->reference);
        $this->assertNull($payment->journal_master_id); // No separate journal

        $loan->refresh();
        $this->assertEquals(1000000, $loan->total_paid);
        $this->assertEquals(2000000, $loan->remaining_amount);
    }

    // ==================== PAYROLL + LOAN INTEGRATION TESTS ====================

    /** @test */
    public function payroll_calculation_includes_loan_deductions()
    {
        $this->seedComponents();
        $this->seedSettings();

        // Create active loan
        $this->createLoan([
            'disbursed_date' => now()->subDays(5)->toDateString(),
            'loan_amount' => 3000000,
            'installment_amount' => 1000000,
            'remaining_amount' => 3000000,
            'status' => 'active',
        ]);

        $period = $this->createPayrollPeriod();
        $service = app(PayrollService::class);
        $service->calculatePayroll($period);

        // Check entry has loan deduction detail
        $entry = PayrollEntry::where('payroll_period_id', $period->id)
            ->where('employee_id', $this->employee->id)->first();

        $loanDetail = PayrollEntryDetail::where('payroll_entry_id', $entry->id)
            ->where('category', 'pinjaman')->first();

        $this->assertNotNull($loanDetail, 'Loan deduction detail should exist');
        $this->assertEquals(1000000, $loanDetail->amount);
        $this->assertEquals('deduction', $loanDetail->type);
        $this->assertTrue($loanDetail->is_auto_calculated);
        $this->assertStringContains('LOAN-', $loanDetail->component_name);
    }

    /** @test */
    public function payroll_calculation_skips_inactive_loans()
    {
        $this->seedComponents();
        $this->seedSettings();

        // Create paid off loan
        $this->createLoan(['status' => 'paid_off']);

        $period = $this->createPayrollPeriod();
        $service = app(PayrollService::class);
        $service->calculatePayroll($period);

        $entry = PayrollEntry::where('payroll_period_id', $period->id)->first();

        $loanDetails = PayrollEntryDetail::where('payroll_entry_id', $entry->id)
            ->where('category', 'pinjaman')->count();

        $this->assertEquals(0, $loanDetails);
    }

    /** @test */
    public function payroll_calculation_uses_remaining_for_last_installment()
    {
        $this->seedComponents();
        $this->seedSettings();

        // Loan with remaining < installment_amount
        $this->createLoan([
            'disbursed_date' => now()->subDays(5)->toDateString(),
            'loan_amount' => 3000000,
            'installment_amount' => 1000000,
            'total_paid' => 2500000,
            'remaining_amount' => 500000,
            'status' => 'active',
        ]);

        $period = $this->createPayrollPeriod();
        $service = app(PayrollService::class);
        $service->calculatePayroll($period);

        $entry = PayrollEntry::where('payroll_period_id', $period->id)->first();
        $loanDetail = PayrollEntryDetail::where('payroll_entry_id', $entry->id)
            ->where('category', 'pinjaman')->first();

        $this->assertEquals(500000, $loanDetail->amount);
    }

    /** @test */
    public function payroll_calculation_handles_multiple_loans()
    {
        $this->seedComponents();
        $this->seedSettings();

        // Two active loans
        $this->createLoan([
            'loan_number' => 'LOAN-TEST-0001',
            'disbursed_date' => now()->subDays(5)->toDateString(),
            'installment_amount' => 500000,
            'remaining_amount' => 1000000,
            'status' => 'active',
        ]);

        $this->createLoan([
            'loan_number' => 'LOAN-TEST-0002',
            'disbursed_date' => now()->subDays(5)->toDateString(),
            'installment_amount' => 300000,
            'remaining_amount' => 600000,
            'status' => 'active',
        ]);

        $period = $this->createPayrollPeriod();
        $service = app(PayrollService::class);
        $service->calculatePayroll($period);

        $entry = PayrollEntry::where('payroll_period_id', $period->id)->first();
        $loanDetails = PayrollEntryDetail::where('payroll_entry_id', $entry->id)
            ->where('category', 'pinjaman')->get();

        $this->assertCount(2, $loanDetails);
        $this->assertEquals(800000, $loanDetails->sum('amount'));
    }

    /** @test */
    public function pay_payroll_with_loan_creates_piutang_karyawan_journal_entry()
    {
        $this->seedComponents();
        $this->seedSettings();

        $this->createLoan([
            'disbursed_date' => now()->subDays(5)->toDateString(),
            'loan_amount' => 3000000,
            'installment_amount' => 1000000,
            'remaining_amount' => 3000000,
            'status' => 'active',
        ]);

        $period = $this->createPayrollPeriod();
        $service = app(PayrollService::class);
        $service->calculatePayroll($period);
        $service->approvePayroll($period);
        $result = $service->payPayroll($period, $this->cashCoa->id);

        $this->assertEquals('paid', $result->status);

        // Verify journal has Piutang Karyawan credit entry
        $piutangEntry = Journal::where('id_journal_master', $result->journal_master_id)
            ->where('id_coa', $this->piutangKaryawanCoa->id)
            ->where('credit', '>', 0)
            ->first();

        $this->assertNotNull($piutangEntry, 'Piutang Karyawan journal entry should exist');
        $this->assertEquals(1000000, $piutangEntry->credit);

        // Verify journal is balanced
        $totalDebit = Journal::where('id_journal_master', $result->journal_master_id)->sum('debit');
        $totalCredit = Journal::where('id_journal_master', $result->journal_master_id)->sum('credit');
        $this->assertEquals($totalDebit, $totalCredit, 'Journal must be balanced');
    }

    /** @test */
    public function pay_payroll_records_loan_payments()
    {
        $this->seedComponents();
        $this->seedSettings();

        $loan = $this->createLoan([
            'disbursed_date' => now()->subDays(5)->toDateString(),
            'loan_amount' => 3000000,
            'installment_amount' => 1000000,
            'remaining_amount' => 3000000,
            'status' => 'active',
        ]);

        $period = $this->createPayrollPeriod();
        $service = app(PayrollService::class);
        $service->calculatePayroll($period);
        $service->approvePayroll($period);
        $service->payPayroll($period, $this->cashCoa->id);

        // Verify loan payment was recorded
        $payment = EmployeeLoanPayment::where('employee_loan_id', $loan->id)
            ->where('payroll_period_id', $period->id)->first();

        $this->assertNotNull($payment, 'Loan payment should be created from payroll');
        $this->assertEquals(1000000, $payment->amount);
        $this->assertEquals('PAYROLL', $payment->reference);

        // Verify loan balance updated
        $loan->refresh();
        $this->assertEquals(1000000, $loan->total_paid);
        $this->assertEquals(2000000, $loan->remaining_amount);
        $this->assertEquals('active', $loan->status);
    }

    /** @test */
    public function pay_payroll_without_loan_has_no_piutang_entry()
    {
        $this->seedComponents();
        $this->seedSettings();

        $period = $this->createPayrollPeriod();
        $service = app(PayrollService::class);
        $service->calculatePayroll($period);
        $service->approvePayroll($period);
        $result = $service->payPayroll($period, $this->cashCoa->id);

        // No piutang karyawan entry
        $piutangEntry = Journal::where('id_journal_master', $result->journal_master_id)
            ->where('id_coa', $this->piutangKaryawanCoa->id)
            ->first();

        $this->assertNull($piutangEntry);
    }

    // ==================== PPH21 TER TESTS ====================

    /** @test */
    public function pph21_ter_rate_lookup()
    {
        // Seed TER rates for category A
        Pph21TerRate::create([
            'category' => 'A',
            'min_income' => 0,
            'max_income' => 5400000,
            'rate' => 0,
        ]);
        Pph21TerRate::create([
            'category' => 'A',
            'min_income' => 5400001,
            'max_income' => 5650000,
            'rate' => 0.25,
        ]);
        Pph21TerRate::create([
            'category' => 'A',
            'min_income' => 5650001,
            'max_income' => 5950000,
            'rate' => 0.50,
        ]);

        $this->assertEquals(0, Pph21TerRate::getRate('A', 5000000));
        $this->assertEquals(0.25, Pph21TerRate::getRate('A', 5500000));
        $this->assertEquals(0.50, Pph21TerRate::getRate('A', 5800000));
    }

    /** @test */
    public function pph21_ter_category_mapping()
    {
        $this->assertEquals('A', Pph21TerRate::getCategoryForPtkp('TK/0'));
        $this->assertEquals('A', Pph21TerRate::getCategoryForPtkp('TK/1'));
        $this->assertEquals('B', Pph21TerRate::getCategoryForPtkp('TK/2'));
        $this->assertEquals('B', Pph21TerRate::getCategoryForPtkp('K/0'));
        $this->assertEquals('C', Pph21TerRate::getCategoryForPtkp('K/2'));
        $this->assertEquals('C', Pph21TerRate::getCategoryForPtkp('K/3'));
    }

    /** @test */
    public function pph21_ter_calculate_tax()
    {
        Pph21TerRate::create([
            'category' => 'A',
            'min_income' => 0,
            'max_income' => 5400000,
            'rate' => 0,
        ]);
        Pph21TerRate::create([
            'category' => 'A',
            'min_income' => 5400001,
            'max_income' => 5650000,
            'rate' => 0.25,
        ]);

        // TK/0, income 5000000 → rate 0% → tax 0
        $this->assertEquals(0, Pph21TerRate::calculateTax('TK/0', 5000000));

        // TK/0, income 5500000 → rate 0.25% → tax 13750
        $this->assertEquals(13750, Pph21TerRate::calculateTax('TK/0', 5500000));
    }

    /** @test */
    public function pph21_component_is_active_by_default()
    {
        $this->seedComponents();

        $pph21 = SalaryComponent::byBusinessUnit($this->unit->id)
            ->where('code', 'PPH21')->first();

        $this->assertNotNull($pph21);
        $this->assertTrue($pph21->is_active);
    }

    /** @test */
    public function pph21_calculates_when_enabled()
    {
        $this->seedComponents();
        $this->seedSettings();

        // Enable PPh21
        PayrollSetting::where('business_unit_id', $this->unit->id)
            ->where('key', 'pph21_enabled')
            ->update(['value' => '1']);

        // Seed TER rate
        Pph21TerRate::create([
            'category' => 'A',
            'min_income' => 0,
            'max_income' => 5400000,
            'rate' => 0.25,
        ]);

        $period = $this->createPayrollPeriod();
        $service = app(PayrollService::class);
        $service->calculatePayroll($period);

        $entry = PayrollEntry::where('payroll_period_id', $period->id)->first();
        $pph21Detail = PayrollEntryDetail::where('payroll_entry_id', $entry->id)
            ->where('category', 'pph21')->first();

        $this->assertNotNull($pph21Detail);
        $this->assertGreaterThan(0, $pph21Detail->amount);
    }

    /** @test */
    public function pph21_zero_when_disabled()
    {
        $this->seedComponents();
        $this->seedSettings();

        // Disable PPh21
        PayrollSetting::where('business_unit_id', $this->unit->id)
            ->where('key', 'pph21_enabled')
            ->update(['value' => '0']);

        $period = $this->createPayrollPeriod();
        $service = app(PayrollService::class);
        $service->calculatePayroll($period);

        $entry = PayrollEntry::where('payroll_period_id', $period->id)->first();
        $pph21Detail = PayrollEntryDetail::where('payroll_entry_id', $entry->id)
            ->where('category', 'pph21')->first();

        // PPh21 should not exist or be 0
        if ($pph21Detail) {
            $this->assertEquals(0, $pph21Detail->amount);
        } else {
            $this->assertNull($pph21Detail);
        }
    }

    /** @test */
    public function pph21_rate_stored_in_payroll_entry()
    {
        $this->seedComponents();
        $this->seedSettings();

        PayrollSetting::where('business_unit_id', $this->unit->id)
            ->where('key', 'pph21_enabled')
            ->update(['value' => '1']);

        Pph21TerRate::create([
            'category' => 'A',
            'min_income' => 0,
            'max_income' => 5400000,
            'rate' => 0.25,
        ]);

        $period = $this->createPayrollPeriod();
        $service = app(PayrollService::class);
        $service->calculatePayroll($period);

        $entry = PayrollEntry::where('payroll_period_id', $period->id)->first();

        $this->assertNotNull($entry->pph21_rate);
        $this->assertEquals(0.25, (float) $entry->pph21_rate);
    }

    // ==================== SALARY COMPONENT PINJAMAN TESTS ====================

    /** @test */
    public function salary_component_has_pinjaman_category()
    {
        $categories = SalaryComponent::CATEGORIES;
        $this->assertArrayHasKey('pinjaman', $categories);
    }

    /** @test */
    public function pot_pinjaman_component_is_seeded()
    {
        $this->seedComponents();

        $potPinjaman = SalaryComponent::byBusinessUnit($this->unit->id)
            ->where('code', 'POT-PINJAMAN')->first();

        $this->assertNotNull($potPinjaman);
        $this->assertEquals('deduction', $potPinjaman->type);
        $this->assertEquals('pinjaman', $potPinjaman->category);
        $this->assertEquals('auto', $potPinjaman->apply_method);
    }

    /** @test */
    public function pinjaman_component_is_skipped_in_normal_calculation()
    {
        $this->seedComponents();
        $this->seedSettings();

        $period = $this->createPayrollPeriod();
        $service = app(PayrollService::class);
        $service->calculatePayroll($period);

        $entry = PayrollEntry::where('payroll_period_id', $period->id)->first();

        // POT-PINJAMAN component should not appear as a regular detail
        $pinjamanFromComponent = PayrollEntryDetail::where('payroll_entry_id', $entry->id)
            ->where('component_name', 'Potongan Pinjaman/Kasbon')->first();

        $this->assertNull($pinjamanFromComponent, 'POT-PINJAMAN should not appear as regular component');
    }

    // ==================== LIVEWIRE LOAN COMPONENT TESTS ====================

    /** @test */
    public function loan_list_component_renders()
    {
        Livewire::test(EmployeeLoanList::class)
            ->assertStatus(200);
    }

    /** @test */
    public function loan_list_shows_loans()
    {
        $loan = $this->createLoan();

        Livewire::test(EmployeeLoanList::class)
            ->assertSee($loan->loan_number)
            ->assertSee($this->employee->name);
    }

    /** @test */
    public function loan_list_can_filter_by_status()
    {
        $activeLoan = $this->createLoan();
        $paidLoan = $this->createLoan([
            'loan_number' => 'LOAN-TEST-0002',
            'status' => 'paid_off',
        ]);

        Livewire::test(EmployeeLoanList::class)
            ->set('filterStatus', 'active')
            ->assertSee($activeLoan->loan_number)
            ->assertDontSee($paidLoan->loan_number);
    }

    /** @test */
    public function loan_list_can_search()
    {
        $loan = $this->createLoan();

        Livewire::test(EmployeeLoanList::class)
            ->set('search', $loan->loan_number)
            ->assertSee($loan->loan_number);
    }

    /** @test */
    public function loan_list_can_void_loan()
    {
        $loan = $this->createLoan();

        Livewire::test(EmployeeLoanList::class)
            ->call('voidLoan', $loan->id)
            ->assertDispatched('alert');

        $loan->refresh();
        $this->assertEquals('void', $loan->status);
    }

    /** @test */
    public function loan_form_component_renders()
    {
        Livewire::test(EmployeeLoanForm::class)
            ->assertStatus(200);
    }

    /** @test */
    public function loan_detail_component_renders()
    {
        $loan = $this->createLoan();

        Livewire::test(EmployeeLoanDetail::class, ['loan' => $loan])
            ->assertStatus(200)
            ->assertSee($loan->loan_number);
    }

    // ==================== LIVEWIRE PAYROLL REPORT TESTS ====================

    /** @test */
    public function payroll_report_recap_renders()
    {
        Livewire::test(PayrollReportRecap::class)
            ->assertStatus(200);
    }

    /** @test */
    public function payroll_report_recap_shows_periods()
    {
        $this->seedComponents();
        $this->seedSettings();

        $period = $this->createPayrollPeriod();
        $service = app(PayrollService::class);
        $service->calculatePayroll($period);

        Livewire::test(PayrollReportRecap::class)
            ->assertSee($period->name);
    }

    /** @test */
    public function payroll_report_recap_can_filter_by_year()
    {
        $period = $this->createPayrollPeriod();
        $period->update(['status' => 'calculated']);

        Livewire::test(PayrollReportRecap::class)
            ->set('filterYear', now()->year)
            ->assertSee($period->name);

        Livewire::test(PayrollReportRecap::class)
            ->set('filterYear', now()->year - 5)
            ->assertDontSee($period->name);
    }

    /** @test */
    public function payroll_report_employee_renders()
    {
        Livewire::test(PayrollReportEmployee::class)
            ->assertStatus(200);
    }

    /** @test */
    public function payroll_report_bpjs_renders()
    {
        Livewire::test(PayrollReportBpjs::class)
            ->assertStatus(200);
    }

    // ==================== EMPLOYEE MODEL RELATIONSHIP TESTS ====================

    /** @test */
    public function employee_has_loans_relationship()
    {
        $loan = $this->createLoan();

        $this->assertCount(1, $this->employee->loans);
        $this->assertCount(1, $this->employee->activeLoans);
    }

    /** @test */
    public function employee_active_loans_filters_correctly()
    {
        $this->createLoan(['status' => 'active']);
        $this->createLoan([
            'loan_number' => 'LOAN-TEST-0002',
            'status' => 'paid_off',
        ]);

        $this->employee->refresh();
        $this->assertCount(2, $this->employee->loans);
        $this->assertCount(1, $this->employee->activeLoans);
    }

    // ==================== BUSINESS UNIT COA MAPPING TESTS ====================

    /** @test */
    public function piutang_karyawan_mapping_exists_in_definitions()
    {
        $definitions = BusinessUnitCoaMapping::getAccountKeyDefinitions();
        $aktivaKeys = collect($definitions['aktiva'])->pluck('key');

        $this->assertTrue($aktivaKeys->contains('piutang_karyawan'));
    }

    /** @test */
    public function business_unit_can_resolve_piutang_karyawan_coa()
    {
        $coa = $this->unit->getCoaByKey('piutang_karyawan');

        $this->assertNotNull($coa);
        $this->assertEquals($this->piutangKaryawanCoa->id, $coa->id);
    }

    // ==================== COMPREHENSIVE INTEGRATION TEST ====================

    /** @test */
    public function full_loan_lifecycle_via_payroll()
    {
        $this->seedComponents();
        $this->seedSettings();

        // 1. Create loan via service
        $loanService = app(EmployeeLoanService::class);
        $loan = $loanService->createLoan([
            'business_unit_id' => $this->unit->id,
            'employee_id' => $this->employee->id,
            'loan_amount' => 2000000,
            'installment_count' => 2,
            'disbursed_date' => now()->toDateString(),
            'payment_coa_id' => $this->cashCoa->id,
        ]);

        $this->assertEquals('active', $loan->status);
        $this->assertEquals(2000000, $loan->remaining_amount);
        $this->assertNotNull($loan->journal_master_id);

        // 2. Calculate payroll (should include loan deduction)
        $period1 = $this->createPayrollPeriod();
        $payrollService = app(PayrollService::class);
        $payrollService->calculatePayroll($period1);

        $entry = PayrollEntry::where('payroll_period_id', $period1->id)->first();
        $loanDetail = PayrollEntryDetail::where('payroll_entry_id', $entry->id)
            ->where('category', 'pinjaman')->first();

        $this->assertNotNull($loanDetail);
        $this->assertEquals(1000000, $loanDetail->amount);

        // 3. Approve and pay payroll
        $payrollService->approvePayroll($period1);
        $result1 = $payrollService->payPayroll($period1, $this->cashCoa->id);

        // 4. Verify loan updated
        $loan->refresh();
        $this->assertEquals(1000000, $loan->total_paid);
        $this->assertEquals(1000000, $loan->remaining_amount);
        $this->assertEquals('active', $loan->status);

        // Verify payment record
        $payments = EmployeeLoanPayment::where('employee_loan_id', $loan->id)->get();
        $this->assertCount(1, $payments);
        $this->assertEquals(1000000, $payments->first()->amount);

        // 5. Verify journal balance for payroll 1
        $totalDebit = Journal::where('id_journal_master', $result1->journal_master_id)->sum('debit');
        $totalCredit = Journal::where('id_journal_master', $result1->journal_master_id)->sum('credit');
        $this->assertEquals($totalDebit, $totalCredit);
    }

    /**
     * Custom assertion: string contains substring.
     */
    protected static function assertStringContains(string $needle, string $haystack, string $message = ''): void
    {
        static::assertThat(
            $haystack,
            static::stringContains($needle),
            $message ?: "Failed asserting that '{$haystack}' contains '{$needle}'."
        );
    }
}
