<?php

namespace Tests\Feature;

use App\Livewire\TaxClosing\TaxClosingWizard;
use App\Models\COA;
use App\Models\FiscalCorrection;
use App\Models\Period;
use App\Models\TaxCalculation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TaxClosingWizardTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Period $currentPeriod;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::withoutEvents(function () {
            return User::factory()->create();
        });
        $this->actingAs($this->user);

        $now = Carbon::now();
        $this->currentPeriod = Period::create([
            'code' => $now->format('Ym'),
            'name' => $now->translatedFormat('F') . ' ' . $now->year,
            'start_date' => $now->copy()->startOfMonth()->format('Y-m-d'),
            'end_date' => $now->copy()->endOfMonth()->format('Y-m-d'),
            'year' => $now->year,
            'month' => $now->month,
            'is_active' => true,
            'is_closed' => false,
        ]);
    }

    // ======================================
    // Route & Page Tests
    // ======================================

    /** @test */
    public function wizard_page_accessible()
    {
        $response = $this->get(route('tax-closing'));
        $response->assertStatus(200);
        $response->assertSee('Perpajakan');
        $response->assertSee('Closing');
    }

    /** @test */
    public function wizard_page_requires_authentication()
    {
        auth()->logout();
        $response = $this->get(route('tax-closing'));
        $response->assertRedirect(route('login'));
    }

    // ======================================
    // Wizard Component Rendering
    // ======================================

    /** @test */
    public function wizard_component_renders()
    {
        Livewire::test(TaxClosingWizard::class)
            ->assertStatus(200)
            ->assertSee('Koreksi Fiskal')
            ->assertSee('Perhitungan Pajak')
            ->assertSee('Closing Bulanan')
            ->assertSee('Closing Tahunan');
    }

    /** @test */
    public function wizard_defaults_to_step_1()
    {
        Livewire::test(TaxClosingWizard::class)
            ->assertSet('currentStep', 1);
    }

    /** @test */
    public function wizard_defaults_to_current_year()
    {
        Livewire::test(TaxClosingWizard::class)
            ->assertSet('selectedYear', (int) date('Y'));
    }

    // ======================================
    // Step Navigation
    // ======================================

    /** @test */
    public function can_navigate_to_specific_step()
    {
        Livewire::test(TaxClosingWizard::class)
            ->call('goToStep', 3)
            ->assertSet('currentStep', 3)
            ->call('goToStep', 5)
            ->assertSet('currentStep', 5)
            ->call('goToStep', 1)
            ->assertSet('currentStep', 1);
    }

    /** @test */
    public function can_navigate_next_step()
    {
        Livewire::test(TaxClosingWizard::class)
            ->assertSet('currentStep', 1)
            ->call('nextStep')
            ->assertSet('currentStep', 2)
            ->call('nextStep')
            ->assertSet('currentStep', 3);
    }

    /** @test */
    public function can_navigate_prev_step()
    {
        Livewire::test(TaxClosingWizard::class)
            ->call('goToStep', 4)
            ->call('prevStep')
            ->assertSet('currentStep', 3)
            ->call('prevStep')
            ->assertSet('currentStep', 2);
    }

    /** @test */
    public function cannot_go_below_step_1()
    {
        Livewire::test(TaxClosingWizard::class)
            ->assertSet('currentStep', 1)
            ->call('prevStep')
            ->assertSet('currentStep', 1);
    }

    /** @test */
    public function cannot_go_above_step_5()
    {
        Livewire::test(TaxClosingWizard::class)
            ->call('goToStep', 5)
            ->call('nextStep')
            ->assertSet('currentStep', 5);
    }

    /** @test */
    public function invalid_step_numbers_are_ignored()
    {
        Livewire::test(TaxClosingWizard::class)
            ->call('goToStep', 0)
            ->assertSet('currentStep', 1)
            ->call('goToStep', 6)
            ->assertSet('currentStep', 1)
            ->call('goToStep', -1)
            ->assertSet('currentStep', 1);
    }

    // ======================================
    // Year Selection
    // ======================================

    /** @test */
    public function changing_year_resets_tax_report()
    {
        Livewire::test(TaxClosingWizard::class)
            ->set('showTaxReport', true)
            ->set('selectedYear', 2025)
            ->assertSet('showTaxReport', false);
    }

    /** @test */
    public function available_years_shows_period_years()
    {
        Period::create([
            'code' => '202501',
            'name' => 'Januari 2025',
            'start_date' => '2025-01-01',
            'end_date' => '2025-01-31',
            'year' => 2025,
            'month' => 1,
            'is_active' => true,
            'is_closed' => false,
        ]);

        $component = Livewire::test(TaxClosingWizard::class);
        $years = $component->viewData('availableYears');
        $this->assertTrue($years->contains((int) date('Y')));
        $this->assertTrue($years->contains(2025));
    }

    // ======================================
    // Step Content Rendering
    // ======================================

    /** @test */
    public function step_1_shows_fiscal_correction_content()
    {
        Livewire::test(TaxClosingWizard::class)
            ->assertSet('currentStep', 1)
            ->assertSee('Tambah Koreksi')
            ->assertSee('Koreksi Fiskal');
    }

    /** @test */
    public function step_2_shows_tax_calculation_content()
    {
        Livewire::test(TaxClosingWizard::class)
            ->call('goToStep', 2)
            ->assertSee('Hitung Pajak')
            ->assertSee('Tarif PPh');
    }

    /** @test */
    public function step_3_shows_tax_journal_content()
    {
        Livewire::test(TaxClosingWizard::class)
            ->call('goToStep', 3)
            ->assertSee('Jurnal Pajak');
    }

    /** @test */
    public function step_4_shows_monthly_closing_content()
    {
        Livewire::test(TaxClosingWizard::class)
            ->call('goToStep', 4)
            ->assertSee('Status Periode Bulanan');
    }

    /** @test */
    public function step_5_shows_yearly_closing_content()
    {
        Livewire::test(TaxClosingWizard::class)
            ->call('goToStep', 5)
            ->assertSee('Tutup Buku Tahunan');
    }

    // ======================================
    // Step 1: Fiscal Correction CRUD
    // ======================================

    /** @test */
    public function can_open_and_close_modal()
    {
        Livewire::test(TaxClosingWizard::class)
            ->assertSet('showModal', false)
            ->call('openModal')
            ->assertSet('showModal', true)
            ->call('closeModal')
            ->assertSet('showModal', false);
    }

    /** @test */
    public function can_add_fiscal_correction()
    {
        Livewire::test(TaxClosingWizard::class)
            ->call('openModal')
            ->set('description', 'Denda Pajak')
            ->set('correction_type', 'positive')
            ->set('category', 'beda_tetap')
            ->set('amount', 5000000)
            ->set('notes', 'Test note')
            ->call('saveFiscalCorrection')
            ->assertSet('showModal', false)
            ->assertDispatched('showAlert');

        $this->assertDatabaseHas('fiscal_corrections', [
            'description' => 'Denda Pajak',
            'correction_type' => 'positive',
            'category' => 'beda_tetap',
            'amount' => 5000000,
        ]);
    }

    /** @test */
    public function can_edit_fiscal_correction()
    {
        $correction = FiscalCorrection::create([
            'year' => (int) date('Y'),
            'description' => 'Original',
            'correction_type' => 'positive',
            'category' => 'beda_tetap',
            'amount' => 1000000,
        ]);

        Livewire::test(TaxClosingWizard::class)
            ->call('editFiscalCorrection', $correction->id)
            ->assertSet('isEditing', true)
            ->assertSet('description', 'Original')
            ->set('description', 'Updated')
            ->call('saveFiscalCorrection')
            ->assertSet('showModal', false);

        $this->assertDatabaseHas('fiscal_corrections', [
            'id' => $correction->id,
            'description' => 'Updated',
        ]);
    }

    /** @test */
    public function can_delete_fiscal_correction()
    {
        $correction = FiscalCorrection::create([
            'year' => (int) date('Y'),
            'description' => 'To Delete',
            'correction_type' => 'negative',
            'category' => 'beda_waktu',
            'amount' => 2000000,
        ]);

        Livewire::test(TaxClosingWizard::class)
            ->call('deleteFiscalCorrection', $correction->id)
            ->assertDispatched('showAlert');

        $this->assertSoftDeleted('fiscal_corrections', [
            'id' => $correction->id,
        ]);
    }

    /** @test */
    public function fiscal_correction_validation_works()
    {
        Livewire::test(TaxClosingWizard::class)
            ->call('openModal')
            ->set('description', '')
            ->set('amount', 0)
            ->call('saveFiscalCorrection')
            ->assertHasErrors(['description', 'amount']);
    }

    // ======================================
    // Step 2: Tax Calculation
    // ======================================

    /** @test */
    public function calculate_tax_shows_report()
    {
        Livewire::test(TaxClosingWizard::class)
            ->call('goToStep', 2)
            ->assertSet('showTaxReport', false)
            ->call('calculateTax')
            ->assertSet('showTaxReport', true);
    }

    /** @test */
    public function clear_tax_report_hides_report()
    {
        Livewire::test(TaxClosingWizard::class)
            ->call('goToStep', 2)
            ->call('calculateTax')
            ->assertSet('showTaxReport', true)
            ->call('clearTaxReport')
            ->assertSet('showTaxReport', false);
    }

    // ======================================
    // Step Statuses
    // ======================================

    /** @test */
    public function step_1_is_always_completed()
    {
        $component = Livewire::test(TaxClosingWizard::class);
        $statuses = $component->viewData('stepStatuses');
        $this->assertTrue($statuses[1]);
    }

    /** @test */
    public function step_2_completed_when_calculation_saved()
    {
        $component = Livewire::test(TaxClosingWizard::class);
        $statuses = $component->viewData('stepStatuses');
        $this->assertFalse($statuses[2]);

        // Save a calculation
        TaxCalculation::create([
            'year' => (int) date('Y'),
            'commercial_profit' => 100000000,
            'fiscal_profit' => 100000000,
            'taxable_income' => 100000000,
            'tax_rate' => 22.00,
            'tax_amount' => 22000000,
            'status' => 'draft',
        ]);

        $component2 = Livewire::test(TaxClosingWizard::class);
        $statuses2 = $component2->viewData('stepStatuses');
        $this->assertTrue($statuses2[2]);
    }

    /** @test */
    public function step_4_completed_when_all_months_closed()
    {
        // Close the only period
        $this->currentPeriod->update(['is_closed' => true, 'closed_at' => now()]);

        $component = Livewire::test(TaxClosingWizard::class);
        $statuses = $component->viewData('stepStatuses');
        $this->assertTrue($statuses[4]);
    }

    /** @test */
    public function step_4_not_completed_when_months_open()
    {
        $component = Livewire::test(TaxClosingWizard::class);
        $statuses = $component->viewData('stepStatuses');
        $this->assertFalse($statuses[4]);
    }

    // ======================================
    // Step 4: Monthly Closing
    // ======================================

    /** @test */
    public function step_4_shows_period_status()
    {
        Livewire::test(TaxClosingWizard::class)
            ->call('goToStep', 4)
            ->assertSee($this->currentPeriod->name)
            ->assertSee('Terbuka');
    }

    /** @test */
    public function can_close_month_from_wizard()
    {
        Livewire::test(TaxClosingWizard::class)
            ->call('goToStep', 4)
            ->call('closeMonth', $this->currentPeriod->id)
            ->assertDispatched('showAlert');

        $this->currentPeriod->refresh();
        $this->assertTrue($this->currentPeriod->is_closed);
    }

    /** @test */
    public function can_reopen_month_from_wizard()
    {
        $this->currentPeriod->update(['is_closed' => true, 'closed_at' => now()]);

        Livewire::test(TaxClosingWizard::class)
            ->call('goToStep', 4)
            ->call('reopenMonth', $this->currentPeriod->id)
            ->assertDispatched('showAlert');

        $this->currentPeriod->refresh();
        $this->assertFalse($this->currentPeriod->is_closed);
    }

    // ======================================
    // Step 5: Yearly Closing Validation
    // ======================================

    /** @test */
    public function yearly_closing_requires_coa_selection()
    {
        Livewire::test(TaxClosingWizard::class)
            ->call('goToStep', 5)
            ->call('closeYear')
            ->assertHasErrors(['summaryCoaId', 'retainedEarningsCoaId']);
    }

    /** @test */
    public function step_3_shows_message_when_no_saved_calculation()
    {
        Livewire::test(TaxClosingWizard::class)
            ->call('goToStep', 3)
            ->assertSee('Simpan perhitungan pajak terlebih dahulu');
    }

    /** @test */
    public function step_5_shows_warning_when_months_not_closed()
    {
        Livewire::test(TaxClosingWizard::class)
            ->call('goToStep', 5)
            ->assertSee('Tutup semua periode bulanan terlebih dahulu');
    }

    // ======================================
    // Wizard Auto-Start Step Detection
    // ======================================

    /** @test */
    public function wizard_auto_starts_at_step_3_when_tax_saved()
    {
        TaxCalculation::create([
            'year' => (int) date('Y'),
            'commercial_profit' => 100000000,
            'fiscal_profit' => 100000000,
            'taxable_income' => 100000000,
            'tax_rate' => 22.00,
            'tax_amount' => 22000000,
            'status' => 'draft',
        ]);

        Livewire::test(TaxClosingWizard::class)
            ->assertSet('currentStep', 3);
    }

    /** @test */
    public function wizard_auto_starts_at_step_4_when_tax_finalized()
    {
        TaxCalculation::create([
            'year' => (int) date('Y'),
            'commercial_profit' => 100000000,
            'fiscal_profit' => 100000000,
            'taxable_income' => 100000000,
            'tax_rate' => 22.00,
            'tax_amount' => 22000000,
            'status' => 'finalized',
        ]);

        Livewire::test(TaxClosingWizard::class)
            ->assertSet('currentStep', 4);
    }

    /** @test */
    public function wizard_auto_starts_at_step_5_when_all_months_closed()
    {
        TaxCalculation::create([
            'year' => (int) date('Y'),
            'commercial_profit' => 100000000,
            'fiscal_profit' => 100000000,
            'taxable_income' => 100000000,
            'tax_rate' => 22.00,
            'tax_amount' => 22000000,
            'status' => 'finalized',
        ]);

        $this->currentPeriod->update(['is_closed' => true, 'closed_at' => now()]);

        Livewire::test(TaxClosingWizard::class)
            ->assertSet('currentStep', 5);
    }

    /** @test */
    public function wizard_auto_detects_step_on_year_change()
    {
        // Save tax calculation for 2025
        TaxCalculation::create([
            'year' => 2025,
            'commercial_profit' => 100000000,
            'fiscal_profit' => 100000000,
            'taxable_income' => 100000000,
            'tax_rate' => 22.00,
            'tax_amount' => 22000000,
            'status' => 'draft',
        ]);

        Period::create([
            'code' => '202501',
            'name' => 'Januari 2025',
            'start_date' => '2025-01-01',
            'end_date' => '2025-01-31',
            'year' => 2025,
            'month' => 1,
            'is_active' => true,
            'is_closed' => false,
        ]);

        // When switching to 2025 (has saved calculation), should jump to step 3
        Livewire::test(TaxClosingWizard::class)
            ->set('selectedYear', 2025)
            ->assertSet('currentStep', 3);
    }
}
