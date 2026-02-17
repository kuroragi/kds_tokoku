<?php

namespace Tests\Feature;

use App\Livewire\Payroll\PayrollDetail;
use App\Livewire\Payroll\PayrollPeriodForm;
use App\Livewire\Payroll\PayrollPeriodList;
use App\Livewire\Payroll\PayrollSettingForm;
use App\Livewire\Payroll\SalaryComponentForm;
use App\Livewire\Payroll\SalaryComponentList;
use App\Models\BusinessUnit;
use App\Models\BusinessUnitCoaMapping;
use App\Models\COA;
use App\Models\Employee;
use App\Models\PayrollEntry;
use App\Models\PayrollEntryDetail;
use App\Models\PayrollPeriod;
use App\Models\PayrollSetting;
use App\Models\Period;
use App\Models\Position;
use App\Models\SalaryComponent;
use App\Models\User;
use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PayrollTest extends TestCase
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

    /**
     * Helper: seed default salary components
     */
    protected function seedComponents(): void
    {
        SalaryComponent::seedDefaultsForBusinessUnit($this->unit->id);
    }

    /**
     * Helper: seed payroll settings
     */
    protected function seedSettings(): void
    {
        PayrollSetting::seedDefaultsForBusinessUnit($this->unit->id);
    }

    /**
     * Helper: create payroll period
     */
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

    // ==================== PAGE ACCESS TESTS ====================

    /** @test */
    public function salary_component_page_is_accessible()
    {
        $response = $this->get(route('salary-component.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function payroll_setting_page_is_accessible()
    {
        $response = $this->get(route('payroll-setting.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function payroll_index_page_is_accessible()
    {
        $response = $this->get(route('payroll.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function payroll_detail_page_is_accessible()
    {
        $period = $this->createPayrollPeriod();
        $response = $this->get(route('payroll.detail', $period));
        $response->assertStatus(200);
    }

    /** @test */
    public function guest_cannot_access_payroll_pages()
    {
        auth()->logout();
        $this->get(route('salary-component.index'))->assertRedirect(route('login'));
        $this->get(route('payroll-setting.index'))->assertRedirect(route('login'));
        $this->get(route('payroll.index'))->assertRedirect(route('login'));
    }

    // ==================== SALARY COMPONENT TESTS ====================

    /** @test */
    public function salary_component_list_renders_successfully()
    {
        Livewire::test(SalaryComponentList::class)->assertStatus(200);
    }

    /** @test */
    public function can_seed_default_salary_components()
    {
        $this->seedComponents();
        $count = SalaryComponent::byBusinessUnit($this->unit->id)->count();
        $this->assertGreaterThanOrEqual(10, $count);
    }

    /** @test */
    public function salary_component_form_can_create()
    {
        Livewire::test(SalaryComponentForm::class)
            ->set('business_unit_id', $this->unit->id)
            ->set('code', 'TJ-TEST')
            ->set('name', 'Test Tunjangan')
            ->set('type', 'earning')
            ->set('category', 'tunjangan_tetap')
            ->set('apply_method', 'template')
            ->set('calculation_type', 'fixed')
            ->set('default_amount', 100000)
            ->call('save')
            ->assertDispatched('refreshSalaryComponentList')
            ->assertDispatched('alert');

        $this->assertDatabaseHas('salary_components', [
            'code' => 'TJ-TEST',
            'name' => 'Test Tunjangan',
        ]);
    }

    /** @test */
    public function salary_component_form_can_update()
    {
        $comp = SalaryComponent::withoutEvents(fn() => SalaryComponent::create([
            'business_unit_id' => $this->unit->id,
            'code' => 'TJ-OLD',
            'name' => 'Old',
            'type' => 'earning',
            'category' => 'tunjangan_tetap',
            'apply_method' => 'template',
            'calculation_type' => 'fixed',
        ]));

        Livewire::test(SalaryComponentForm::class)
            ->call('editSalaryComponent', $comp->id)
            ->set('name', 'Updated Name')
            ->call('save')
            ->assertDispatched('refreshSalaryComponentList');

        $this->assertDatabaseHas('salary_components', [
            'id' => $comp->id,
            'name' => 'Updated Name',
        ]);
    }

    /** @test */
    public function salary_component_form_validates_required()
    {
        Livewire::test(SalaryComponentForm::class)
            ->set('code', '')
            ->set('name', '')
            ->call('save')
            ->assertHasErrors(['code', 'name']);
    }

    // ==================== PAYROLL SETTING TESTS ====================

    /** @test */
    public function payroll_setting_form_renders()
    {
        Livewire::test(PayrollSettingForm::class)->assertStatus(200);
    }

    /** @test */
    public function can_seed_default_payroll_settings()
    {
        $this->seedSettings();
        $count = PayrollSetting::where('business_unit_id', $this->unit->id)->count();
        $this->assertGreaterThanOrEqual(5, $count);
    }

    /** @test */
    public function payroll_setting_value_can_be_retrieved()
    {
        $this->seedSettings();
        $rate = PayrollSetting::getValue($this->unit->id, 'bpjs_kes_company_rate');
        $this->assertEquals(4, $rate);
    }

    // ==================== PAYROLL PERIOD TESTS ====================

    /** @test */
    public function payroll_period_list_renders()
    {
        Livewire::test(PayrollPeriodList::class)->assertStatus(200);
    }

    /** @test */
    public function payroll_period_form_can_create()
    {
        Livewire::test(PayrollPeriodForm::class)
            ->set('business_unit_id', $this->unit->id)
            ->set('month', 6)
            ->set('year', 2026)
            ->call('save')
            ->assertDispatched('refreshPayrollPeriodList');

        $this->assertDatabaseHas('payroll_periods', [
            'month' => 6,
            'year' => 2026,
            'status' => 'draft',
        ]);
    }

    /** @test */
    public function payroll_period_form_prevents_duplicate()
    {
        $this->createPayrollPeriod(['month' => 3, 'year' => 2026]);

        Livewire::test(PayrollPeriodForm::class)
            ->set('business_unit_id', $this->unit->id)
            ->set('month', 3)
            ->set('year', 2026)
            ->call('save')
            ->assertHasErrors();
    }

    /** @test */
    public function payroll_period_list_can_filter_by_status()
    {
        $this->createPayrollPeriod(['month' => 1, 'year' => 2026, 'name' => 'Gaji Jan 2026']);
        $this->createPayrollPeriod(['month' => 2, 'year' => 2026, 'name' => 'Gaji Feb 2026', 'status' => 'calculated']);

        Livewire::test(PayrollPeriodList::class)
            ->set('filterStatus', 'draft')
            ->assertSee('Gaji Jan 2026')
            ->assertDontSee('Gaji Feb 2026');
    }

    /** @test */
    public function payroll_period_list_can_search()
    {
        $this->createPayrollPeriod(['month' => 5, 'year' => 2026, 'name' => 'Gaji Mei 2026']);
        $this->createPayrollPeriod(['month' => 6, 'year' => 2026, 'name' => 'Gaji Juni 2026']);

        Livewire::test(PayrollPeriodList::class)
            ->set('search', 'Mei')
            ->assertSee('Gaji Mei 2026')
            ->assertDontSee('Gaji Juni 2026');
    }

    /** @test */
    public function can_delete_draft_payroll_period()
    {
        $period = $this->createPayrollPeriod();

        Livewire::test(PayrollPeriodList::class)
            ->call('deletePeriod', $period->id)
            ->assertDispatched('alert');

        $this->assertDatabaseMissing('payroll_periods', ['id' => $period->id]);
    }

    /** @test */
    public function cannot_delete_paid_payroll_period()
    {
        $period = $this->createPayrollPeriod(['status' => 'paid']);

        Livewire::test(PayrollPeriodList::class)
            ->call('deletePeriod', $period->id)
            ->assertDispatched('alert');

        $this->assertDatabaseHas('payroll_periods', ['id' => $period->id, 'deleted_at' => null]);
    }

    // ==================== PAYROLL SERVICE TESTS ====================

    /** @test */
    public function payroll_service_can_calculate_payroll()
    {
        $this->seedComponents();
        $this->seedSettings();

        $period = $this->createPayrollPeriod();
        $service = app(PayrollService::class);

        $result = $service->calculatePayroll($period);

        $this->assertEquals('calculated', $result->status);
        $this->assertEquals(1, $result->entries()->count());

        $entry = $result->entries()->first();
        $this->assertEquals($this->employee->id, $entry->employee_id);
        $this->assertEquals(5000000, $entry->base_salary);
        $this->assertGreaterThan(0, $entry->total_earnings);
    }

    /** @test */
    public function payroll_service_calculates_gaji_pokok_correctly()
    {
        $this->seedComponents();
        $this->seedSettings();

        $period = $this->createPayrollPeriod();
        $service = app(PayrollService::class);
        $service->calculatePayroll($period);

        $entry = $period->entries()->first();
        $gpDetail = $entry->details()->whereHas('salaryComponent', function ($q) {
            $q->where('code', 'GP');
        })->first();

        $this->assertNotNull($gpDetail);
        $this->assertEquals(5000000, $gpDetail->amount);
        $this->assertEquals('earning', $gpDetail->type);
    }

    /** @test */
    public function payroll_service_calculates_bpjs_kes_company()
    {
        $this->seedComponents();
        $this->seedSettings();

        $period = $this->createPayrollPeriod();
        $service = app(PayrollService::class);
        $service->calculatePayroll($period);

        $entry = $period->entries()->first();
        $bpjsDetail = $entry->details()->whereHas('salaryComponent', function ($q) {
            $q->where('code', 'BPJS-KES-C');
        })->first();

        $this->assertNotNull($bpjsDetail);
        // 4% of 5,000,000 = 200,000
        $this->assertEquals(200000, $bpjsDetail->amount);
        $this->assertEquals('benefit', $bpjsDetail->type);
    }

    /** @test */
    public function payroll_service_calculates_bpjs_kes_employee()
    {
        $this->seedComponents();
        $this->seedSettings();

        $period = $this->createPayrollPeriod();
        $service = app(PayrollService::class);
        $service->calculatePayroll($period);

        $entry = $period->entries()->first();
        $bpjsDetail = $entry->details()->whereHas('salaryComponent', function ($q) {
            $q->where('code', 'BPJS-KES-E');
        })->first();

        $this->assertNotNull($bpjsDetail);
        // 1% of 5,000,000 = 50,000
        $this->assertEquals(50000, $bpjsDetail->amount);
        $this->assertEquals('deduction', $bpjsDetail->type);
    }

    /** @test */
    public function payroll_service_applies_bpjs_kes_cap()
    {
        $this->seedComponents();
        $this->seedSettings();

        // Employee with salary above cap
        Employee::withoutEvents(function () {
            $this->employee->update(['base_salary' => 15000000]);
        });

        $period = $this->createPayrollPeriod();
        $service = app(PayrollService::class);
        $service->calculatePayroll($period);

        $entry = $period->entries()->first();
        $bpjsDetail = $entry->details()->whereHas('salaryComponent', function ($q) {
            $q->where('code', 'BPJS-KES-C');
        })->first();

        $this->assertNotNull($bpjsDetail);
        // 4% of cap(12,000,000) = 480,000 (not 4% of 15M = 600,000)
        $this->assertEquals(480000, $bpjsDetail->amount);
    }

    /** @test */
    public function payroll_service_calculates_bpjs_jkk()
    {
        $this->seedComponents();
        $this->seedSettings();

        $period = $this->createPayrollPeriod();
        $service = app(PayrollService::class);
        $service->calculatePayroll($period);

        $entry = $period->entries()->first();
        $detail = $entry->details()->whereHas('salaryComponent', function ($q) {
            $q->where('code', 'BPJS-JKK');
        })->first();

        $this->assertNotNull($detail);
        // 0.24% of 5,000,000 = 12,000
        $this->assertEquals(12000, $detail->amount);
    }

    /** @test */
    public function payroll_service_calculates_bpjs_jkm()
    {
        $this->seedComponents();
        $this->seedSettings();

        $period = $this->createPayrollPeriod();
        $service = app(PayrollService::class);
        $service->calculatePayroll($period);

        $entry = $period->entries()->first();
        $detail = $entry->details()->whereHas('salaryComponent', function ($q) {
            $q->where('code', 'BPJS-JKM');
        })->first();

        $this->assertNotNull($detail);
        // 0.30% of 5,000,000 = 15,000
        $this->assertEquals(15000, $detail->amount);
    }

    /** @test */
    public function payroll_service_calculates_bpjs_jht()
    {
        $this->seedComponents();
        $this->seedSettings();

        $period = $this->createPayrollPeriod();
        $service = app(PayrollService::class);
        $service->calculatePayroll($period);

        $entry = $period->entries()->first();

        // Company: 3.7% of 5M = 185,000
        $jhtC = $entry->details()->whereHas('salaryComponent', function ($q) {
            $q->where('code', 'BPJS-JHT-C');
        })->first();
        $this->assertNotNull($jhtC);
        $this->assertEquals(185000, $jhtC->amount);

        // Employee: 2% of 5M = 100,000
        $jhtE = $entry->details()->whereHas('salaryComponent', function ($q) {
            $q->where('code', 'BPJS-JHT-E');
        })->first();
        $this->assertNotNull($jhtE);
        $this->assertEquals(100000, $jhtE->amount);
    }

    /** @test */
    public function payroll_service_calculates_bpjs_jp()
    {
        $this->seedComponents();
        $this->seedSettings();

        $period = $this->createPayrollPeriod();
        $service = app(PayrollService::class);
        $service->calculatePayroll($period);

        $entry = $period->entries()->first();

        // Company: 2% of 5M = 100,000
        $jpC = $entry->details()->whereHas('salaryComponent', function ($q) {
            $q->where('code', 'BPJS-JP-C');
        })->first();
        $this->assertNotNull($jpC);
        $this->assertEquals(100000, $jpC->amount);

        // Employee: 1% of 5M = 50,000
        $jpE = $entry->details()->whereHas('salaryComponent', function ($q) {
            $q->where('code', 'BPJS-JP-E');
        })->first();
        $this->assertNotNull($jpE);
        $this->assertEquals(50000, $jpE->amount);
    }

    /** @test */
    public function payroll_service_applies_bpjs_jp_cap()
    {
        $this->seedComponents();
        $this->seedSettings();

        // Employee above JP cap
        Employee::withoutEvents(function () {
            $this->employee->update(['base_salary' => 12000000]);
        });

        $period = $this->createPayrollPeriod();
        $service = app(PayrollService::class);
        $service->calculatePayroll($period);

        $entry = $period->entries()->first();

        // JP cap is ~10,042,300; Company 2% of cap
        $jpCap = PayrollSetting::getValue($this->unit->id, 'bpjs_jp_cap');
        $expectedCompany = (int) round($jpCap * 2 / 100);

        $jpC = $entry->details()->whereHas('salaryComponent', function ($q) {
            $q->where('code', 'BPJS-JP-C');
        })->first();
        $this->assertNotNull($jpC);
        $this->assertEquals($expectedCompany, $jpC->amount);
    }

    /** @test */
    public function payroll_net_salary_equals_earnings_minus_deductions()
    {
        $this->seedComponents();
        $this->seedSettings();

        $period = $this->createPayrollPeriod();
        $service = app(PayrollService::class);
        $service->calculatePayroll($period);

        $entry = $period->entries()->first();
        $this->assertEquals(
            $entry->total_earnings - $entry->total_deductions,
            $entry->net_salary
        );
    }

    /** @test */
    public function payroll_totals_match_entry_sums()
    {
        $this->seedComponents();
        $this->seedSettings();

        $period = $this->createPayrollPeriod();
        $service = app(PayrollService::class);
        $result = $service->calculatePayroll($period);

        $this->assertEquals(
            $result->entries()->sum('total_earnings'),
            $result->total_earnings
        );
        $this->assertEquals(
            $result->entries()->sum('total_benefits'),
            $result->total_benefits
        );
        $this->assertEquals(
            $result->entries()->sum('net_salary'),
            $result->total_net
        );
    }

    /** @test */
    public function payroll_cannot_calculate_on_approved_status()
    {
        $period = $this->createPayrollPeriod(['status' => 'approved']);
        $service = app(PayrollService::class);

        $this->expectException(\Exception::class);
        $service->calculatePayroll($period);
    }

    /** @test */
    public function payroll_throws_when_no_employees()
    {
        $this->seedComponents();
        $this->seedSettings();

        Employee::withoutEvents(function () {
            $this->employee->update(['base_salary' => 0]);
        });

        $period = $this->createPayrollPeriod();
        $service = app(PayrollService::class);

        $this->expectException(\Exception::class);
        $service->calculatePayroll($period);
    }

    // ==================== MANUAL ITEM TESTS ====================

    /** @test */
    public function can_add_manual_item_to_entry()
    {
        $this->seedComponents();
        $this->seedSettings();

        $period = $this->createPayrollPeriod();
        $service = app(PayrollService::class);
        $service->calculatePayroll($period);

        $entry = $period->entries()->first();
        $prevDeductions = $entry->total_deductions;

        $detail = $service->addManualItem(
            $entry,
            null,
            'Potongan Keterlambatan',
            'deduction',
            'potongan',
            50000,
            '3x terlambat'
        );

        $this->assertNotNull($detail);
        $this->assertFalse($detail->is_auto_calculated);
        $this->assertEquals(50000, $detail->amount);

        $entry->refresh();
        $this->assertEquals($prevDeductions + 50000, $entry->total_deductions);
    }

    /** @test */
    public function can_remove_manual_item()
    {
        $this->seedComponents();
        $this->seedSettings();

        $period = $this->createPayrollPeriod();
        $service = app(PayrollService::class);
        $service->calculatePayroll($period);

        $entry = $period->entries()->first();
        $detail = $service->addManualItem($entry, null, 'Test', 'deduction', 'potongan', 50000);

        $service->removeManualItem($detail);

        $this->assertDatabaseMissing('payroll_entry_details', ['id' => $detail->id]);
    }

    /** @test */
    public function cannot_remove_auto_calculated_item()
    {
        $this->seedComponents();
        $this->seedSettings();

        $period = $this->createPayrollPeriod();
        $service = app(PayrollService::class);
        $service->calculatePayroll($period);

        $entry = $period->entries()->first();
        $autoDetail = $entry->details()->where('is_auto_calculated', true)->first();

        $this->expectException(\Exception::class);
        $service->removeManualItem($autoDetail);
    }

    /** @test */
    public function cannot_add_manual_item_on_approved_period()
    {
        $this->seedComponents();
        $this->seedSettings();

        $period = $this->createPayrollPeriod();
        $service = app(PayrollService::class);
        $service->calculatePayroll($period);
        $service->approvePayroll($period);

        $entry = $period->entries()->first();

        $this->expectException(\Exception::class);
        $service->addManualItem($entry, null, 'Test', 'deduction', 'potongan', 50000);
    }

    // ==================== APPROVE & PAY TESTS ====================

    /** @test */
    public function can_approve_calculated_payroll()
    {
        $this->seedComponents();
        $this->seedSettings();

        $period = $this->createPayrollPeriod();
        $service = app(PayrollService::class);
        $service->calculatePayroll($period);

        $result = $service->approvePayroll($period);
        $this->assertEquals('approved', $result->status);
    }

    /** @test */
    public function cannot_approve_draft_payroll()
    {
        $period = $this->createPayrollPeriod();
        $service = app(PayrollService::class);

        $this->expectException(\Exception::class);
        $service->approvePayroll($period);
    }

    /** @test */
    public function can_pay_approved_payroll_and_creates_journal()
    {
        $this->seedComponents();
        $this->seedSettings();

        $period = $this->createPayrollPeriod();
        $service = app(PayrollService::class);
        $service->calculatePayroll($period);
        $service->approvePayroll($period);

        $result = $service->payPayroll($period, $this->cashCoa->id);

        $this->assertEquals('paid', $result->status);
        $this->assertNotNull($result->journal_master_id);
        $this->assertNotNull($result->paid_date);
        $this->assertEquals($this->cashCoa->id, $result->payment_coa_id);

        // Verify journal was created
        $this->assertDatabaseHas('journal_masters', [
            'id' => $result->journal_master_id,
        ]);
    }

    /** @test */
    public function cannot_pay_draft_payroll()
    {
        $period = $this->createPayrollPeriod();
        $service = app(PayrollService::class);

        $this->expectException(\Exception::class);
        $service->payPayroll($period, $this->cashCoa->id);
    }

    /** @test */
    public function pay_journal_is_balanced()
    {
        $this->seedComponents();
        $this->seedSettings();

        $period = $this->createPayrollPeriod();
        $service = app(PayrollService::class);
        $service->calculatePayroll($period);
        $service->approvePayroll($period);

        $result = $service->payPayroll($period, $this->cashCoa->id);

        // Verify journal balance
        $journalId = $result->journal_master_id;
        $totalDebit = \App\Models\Journal::where('id_journal_master', $journalId)->sum('debit');
        $totalCredit = \App\Models\Journal::where('id_journal_master', $journalId)->sum('credit');

        $this->assertEquals($totalDebit, $totalCredit, 'Journal must be balanced');
        $this->assertGreaterThan(0, $totalDebit);
    }

    // ==================== VOID TESTS ====================

    /** @test */
    public function can_void_draft_payroll()
    {
        $period = $this->createPayrollPeriod();
        $service = app(PayrollService::class);

        $result = $service->voidPayroll($period);
        $this->assertEquals('void', $result->status);
    }

    /** @test */
    public function can_void_calculated_payroll()
    {
        $this->seedComponents();
        $this->seedSettings();

        $period = $this->createPayrollPeriod();
        $service = app(PayrollService::class);
        $service->calculatePayroll($period);

        $result = $service->voidPayroll($period);
        $this->assertEquals('void', $result->status);
    }

    /** @test */
    public function cannot_void_paid_payroll()
    {
        $this->seedComponents();
        $this->seedSettings();

        $period = $this->createPayrollPeriod();
        $service = app(PayrollService::class);
        $service->calculatePayroll($period);
        $service->approvePayroll($period);
        $service->payPayroll($period, $this->cashCoa->id);

        $this->expectException(\Exception::class);
        $service->voidPayroll($period);
    }

    // ==================== PAYROLL DETAIL LIVEWIRE TESTS ====================

    /** @test */
    public function payroll_detail_component_renders()
    {
        $period = $this->createPayrollPeriod();
        Livewire::test(PayrollDetail::class, ['payrollPeriod' => $period])
            ->assertStatus(200);
    }

    /** @test */
    public function payroll_detail_can_calculate()
    {
        $this->seedComponents();
        $this->seedSettings();

        $period = $this->createPayrollPeriod();

        Livewire::test(PayrollDetail::class, ['payrollPeriod' => $period])
            ->call('calculate')
            ->assertDispatched('alert');

        $period->refresh();
        $this->assertEquals('calculated', $period->status);
    }

    /** @test */
    public function payroll_detail_can_approve()
    {
        $this->seedComponents();
        $this->seedSettings();

        $period = $this->createPayrollPeriod();
        $service = app(PayrollService::class);
        $service->calculatePayroll($period);

        Livewire::test(PayrollDetail::class, ['payrollPeriod' => $period])
            ->call('approve')
            ->assertDispatched('alert');

        $period->refresh();
        $this->assertEquals('approved', $period->status);
    }

    /** @test */
    public function payroll_detail_search_filters_entries()
    {
        $this->seedComponents();
        $this->seedSettings();

        // Create second employee
        $emp2 = Employee::withoutEvents(fn() => Employee::create([
            'business_unit_id' => $this->unit->id,
            'position_id' => $this->position->id,
            'code' => 'EMP-002',
            'name' => 'Jane Smith',
            'base_salary' => 6000000,
            'ptkp_status' => 'K/0',
            'is_active' => true,
        ]));

        $period = $this->createPayrollPeriod();
        $service = app(PayrollService::class);
        $service->calculatePayroll($period);

        Livewire::test(PayrollDetail::class, ['payrollPeriod' => $period])
            ->set('search', 'John')
            ->assertSee('John Doe')
            ->assertDontSee('Jane Smith');
    }

    // ==================== TEMPLATE & COMPONENT ASSIGNMENT TESTS ====================

    /** @test */
    public function employee_effective_salary_uses_employee_override()
    {
        $comp = SalaryComponent::withoutEvents(fn() => SalaryComponent::create([
            'business_unit_id' => $this->unit->id,
            'code' => 'TJ-TST',
            'name' => 'Test',
            'type' => 'earning',
            'category' => 'tunjangan_tetap',
            'apply_method' => 'template',
            'calculation_type' => 'fixed',
            'default_amount' => 100000,
        ]));

        // Position assignment
        \App\Models\PositionSalaryComponent::withoutEvents(fn() => \App\Models\PositionSalaryComponent::create([
            'position_id' => $this->position->id,
            'salary_component_id' => $comp->id,
            'amount' => 200000,
        ]));

        // Employee override
        \App\Models\EmployeeSalaryComponent::withoutEvents(fn() => \App\Models\EmployeeSalaryComponent::create([
            'employee_id' => $this->employee->id,
            'salary_component_id' => $comp->id,
            'amount' => 300000,
        ]));

        $amount = $this->employee->getEffectiveSalaryAmount($comp);
        $this->assertEquals(300000, $amount); // Employee override wins
    }

    /** @test */
    public function employee_effective_salary_falls_back_to_position()
    {
        $comp = SalaryComponent::withoutEvents(fn() => SalaryComponent::create([
            'business_unit_id' => $this->unit->id,
            'code' => 'TJ-TST2',
            'name' => 'Test2',
            'type' => 'earning',
            'category' => 'tunjangan_tetap',
            'apply_method' => 'template',
            'calculation_type' => 'fixed',
            'default_amount' => 100000,
        ]));

        \App\Models\PositionSalaryComponent::withoutEvents(fn() => \App\Models\PositionSalaryComponent::create([
            'position_id' => $this->position->id,
            'salary_component_id' => $comp->id,
            'amount' => 250000,
        ]));

        $amount = $this->employee->getEffectiveSalaryAmount($comp);
        $this->assertEquals(250000, $amount); // Position template
    }

    /** @test */
    public function employee_effective_salary_falls_back_to_component_default()
    {
        $comp = SalaryComponent::withoutEvents(fn() => SalaryComponent::create([
            'business_unit_id' => $this->unit->id,
            'code' => 'TJ-TST3',
            'name' => 'Test3',
            'type' => 'earning',
            'category' => 'tunjangan_tetap',
            'apply_method' => 'template',
            'calculation_type' => 'fixed',
            'default_amount' => 150000,
        ]));

        $amount = $this->employee->getEffectiveSalaryAmount($comp);
        $this->assertEquals(150000, $amount); // Component default
    }

    // ==================== MULTIPLE EMPLOYEE TESTS ====================

    /** @test */
    public function payroll_calculates_for_multiple_employees()
    {
        $this->seedComponents();
        $this->seedSettings();

        Employee::withoutEvents(fn() => Employee::create([
            'business_unit_id' => $this->unit->id,
            'position_id' => $this->position->id,
            'code' => 'EMP-002',
            'name' => 'Jane Smith',
            'base_salary' => 7000000,
            'ptkp_status' => 'K/1',
            'is_active' => true,
        ]));

        $period = $this->createPayrollPeriod();
        $service = app(PayrollService::class);
        $service->calculatePayroll($period);

        $this->assertEquals(2, $period->entries()->count());
    }

    /** @test */
    public function payroll_skips_inactive_employees()
    {
        $this->seedComponents();
        $this->seedSettings();

        Employee::withoutEvents(fn() => Employee::create([
            'business_unit_id' => $this->unit->id,
            'position_id' => $this->position->id,
            'code' => 'EMP-003',
            'name' => 'Inactive Person',
            'base_salary' => 3000000,
            'is_active' => false,
        ]));

        $period = $this->createPayrollPeriod();
        $service = app(PayrollService::class);
        $service->calculatePayroll($period);

        $this->assertEquals(1, $period->entries()->count());
    }

    /** @test */
    public function payroll_recalculate_replaces_auto_entries()
    {
        $this->seedComponents();
        $this->seedSettings();

        $period = $this->createPayrollPeriod();
        $service = app(PayrollService::class);
        $service->calculatePayroll($period);

        $entry = $period->entries()->first();
        $firstTotal = $entry->total_earnings;

        // Change salary
        Employee::withoutEvents(function () {
            $this->employee->update(['base_salary' => 8000000]);
        });

        // Recalculate
        $service->calculatePayroll($period);

        $entry = $period->fresh()->entries()->first();
        $this->assertEquals(8000000, $entry->total_earnings);
        $this->assertNotEquals($firstTotal, $entry->total_earnings);
    }

    // ==================== STATUS FLOW TESTS ====================

    /** @test */
    public function payroll_period_status_helpers()
    {
        $period = $this->createPayrollPeriod(['status' => 'draft']);
        $this->assertTrue($period->isDraft());
        $this->assertTrue($period->canCalculate());
        $this->assertFalse($period->canApprove());
        $this->assertFalse($period->canPay());
        $this->assertTrue($period->canVoid());

        $period->status = 'calculated';
        $this->assertTrue($period->isCalculated());
        $this->assertTrue($period->canCalculate());
        $this->assertTrue($period->canApprove());
        $this->assertFalse($period->canPay());
        $this->assertTrue($period->canVoid());

        $period->status = 'approved';
        $this->assertTrue($period->isApproved());
        $this->assertFalse($period->canCalculate());
        $this->assertFalse($period->canApprove());
        $this->assertTrue($period->canPay());
        $this->assertTrue($period->canVoid());

        $period->status = 'paid';
        $this->assertTrue($period->isPaid());
        $this->assertFalse($period->canCalculate());
        $this->assertFalse($period->canApprove());
        $this->assertFalse($period->canPay());
        $this->assertFalse($period->canVoid());

        $period->status = 'void';
        $this->assertTrue($period->isVoid());
        $this->assertFalse($period->canCalculate());
        $this->assertFalse($period->canVoid());
    }
}
