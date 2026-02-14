<?php

namespace Tests\Feature;

use App\Livewire\TaxCalculation\TaxCalculationIndex;
use App\Models\COA;
use App\Models\FiscalCorrection;
use App\Models\JournalMaster;
use App\Models\LossCompensation;
use App\Models\Period;
use App\Models\TaxCalculation;
use App\Models\User;
use App\Services\JournalService;
use App\Services\TaxService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TaxCalculationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Period $currentPeriod;
    protected Period $decemberPeriod;
    protected JournalService $journalService;
    protected TaxService $taxService;
    protected int $year;

    protected COA $cashAccount;
    protected COA $revenueAccount;
    protected COA $expenseAccount;
    protected COA $taxExpenseAccount;
    protected COA $taxPayableAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->journalService = new JournalService();
        $this->taxService = app(TaxService::class);
        $this->year = (int) date('Y');

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

        // December period for tax journal
        if ($now->month != 12) {
            $this->decemberPeriod = Period::create([
                'code' => "{$this->year}12",
                'name' => "Desember {$this->year}",
                'start_date' => "{$this->year}-12-01",
                'end_date' => "{$this->year}-12-31",
                'year' => $this->year,
                'month' => 12,
                'is_active' => true,
                'is_closed' => false,
            ]);
        } else {
            $this->decemberPeriod = $this->currentPeriod;
        }

        // COA accounts
        $this->cashAccount = COA::create([
            'code' => '1101', 'name' => 'Kas', 'type' => 'aktiva',
            'level' => 2, 'order' => 1, 'is_active' => true, 'is_leaf_account' => true,
        ]);
        $this->revenueAccount = COA::create([
            'code' => '4101', 'name' => 'Pendapatan Jasa', 'type' => 'pendapatan',
            'level' => 2, 'order' => 1, 'is_active' => true, 'is_leaf_account' => true,
        ]);
        $this->expenseAccount = COA::create([
            'code' => '5101', 'name' => 'Beban Gaji', 'type' => 'beban',
            'level' => 2, 'order' => 1, 'is_active' => true, 'is_leaf_account' => true,
        ]);
        $this->taxExpenseAccount = COA::create([
            'code' => '5901', 'name' => 'Beban Pajak Penghasilan', 'type' => 'beban',
            'level' => 2, 'order' => 2, 'is_active' => true, 'is_leaf_account' => true,
        ]);
        $this->taxPayableAccount = COA::create([
            'code' => '2201', 'name' => 'Utang Pajak', 'type' => 'pasiva',
            'level' => 2, 'order' => 1, 'is_active' => true, 'is_leaf_account' => true,
        ]);
    }

    // ======================================
    // Helpers
    // ======================================

    private function createRevenue(int $amount, ?string $date = null)
    {
        return $this->journalService->createJournalEntry([
            'journal_date' => $date ?? now()->format('Y-m-d'),
            'id_period' => $this->currentPeriod->id,
            'status' => 'posted',
            'type' => 'general',
            'entries' => [
                ['coa_code' => '1101', 'description' => 'Penerimaan kas', 'debit' => $amount, 'credit' => 0],
                ['coa_code' => '4101', 'description' => 'Pendapatan jasa', 'debit' => 0, 'credit' => $amount],
            ],
        ]);
    }

    private function createExpense(int $amount, ?string $date = null)
    {
        return $this->journalService->createJournalEntry([
            'journal_date' => $date ?? now()->format('Y-m-d'),
            'id_period' => $this->currentPeriod->id,
            'status' => 'posted',
            'type' => 'general',
            'entries' => [
                ['coa_code' => '5101', 'description' => 'Pembayaran gaji', 'debit' => $amount, 'credit' => 0],
                ['coa_code' => '1101', 'description' => 'Pengeluaran kas', 'debit' => 0, 'credit' => $amount],
            ],
        ]);
    }

    // ======================================
    // Route & Page Tests
    // ======================================

    /** @test */
    public function tax_calculation_page_accessible()
    {
        $response = $this->get(route('tax-closing'));
        $response->assertStatus(200);
        $response->assertSee('Perpajakan');
    }

    /** @test */
    public function tax_calculation_page_requires_authentication()
    {
        auth()->logout();
        $response = $this->get(route('tax-closing'));
        $response->assertRedirect(route('login'));
    }

    // ======================================
    // Livewire Component Tests
    // ======================================

    /** @test */
    public function livewire_component_renders()
    {
        Livewire::test(TaxCalculationIndex::class)
            ->assertStatus(200)
            ->assertSee('Hitung Pajak');
    }

    /** @test */
    public function component_defaults_to_current_year()
    {
        $component = Livewire::test(TaxCalculationIndex::class);
        $this->assertEquals($this->year, $component->get('selectedYear'));
    }

    /** @test */
    public function shows_empty_state_before_calculation()
    {
        Livewire::test(TaxCalculationIndex::class)
            ->assertSee('Pilih tahun dan klik');
    }

    // ======================================
    // TaxService: Commercial Profit
    // ======================================

    /** @test */
    public function service_calculates_commercial_profit()
    {
        $this->createRevenue(100000000); // Revenue 100M
        $this->createExpense(60000000);  // Expense 60M

        $result = $this->taxService->getCommercialProfit($this->year);
        $this->assertEquals(100000000, $result['total_pendapatan']);
        $this->assertEquals(60000000, $result['total_beban']);
        $this->assertEquals(40000000, $result['net_income']);
    }

    // ======================================
    // TaxService: calculateTax
    // ======================================

    /** @test */
    public function service_calculates_tax_without_corrections()
    {
        $this->createRevenue(100000000);
        $this->createExpense(60000000);

        $calc = $this->taxService->calculateTax($this->year);

        $this->assertEquals(40000000, $calc['commercial_profit']);
        $this->assertEquals(0, $calc['total_positive_correction']);
        $this->assertEquals(0, $calc['total_negative_correction']);
        $this->assertEquals(40000000, $calc['fiscal_profit']);
        $this->assertEquals(0, $calc['loss_compensation_amount']);
        $this->assertEquals(40000000, $calc['taxable_income']);
        $this->assertEquals(8800000, $calc['tax_amount']); // 40M × 22% = 8.8M
    }

    /** @test */
    public function service_calculates_tax_with_positive_corrections()
    {
        $this->createRevenue(100000000);
        $this->createExpense(60000000);

        FiscalCorrection::create([
            'year' => $this->year,
            'description' => 'Denda Pajak',
            'correction_type' => 'positive',
            'category' => 'beda_tetap',
            'amount' => 5000000,
        ]);

        $calc = $this->taxService->calculateTax($this->year);

        $this->assertEquals(40000000, $calc['commercial_profit']);
        $this->assertEquals(5000000, $calc['total_positive_correction']);
        $this->assertEquals(45000000, $calc['fiscal_profit']);
        $this->assertEquals(45000000, $calc['taxable_income']);
        $this->assertEquals(9900000, $calc['tax_amount']); // 45M × 22%
    }

    /** @test */
    public function service_calculates_tax_with_negative_corrections()
    {
        $this->createRevenue(100000000);
        $this->createExpense(60000000);

        FiscalCorrection::create([
            'year' => $this->year,
            'description' => 'Pendapatan Bunga Deposito',
            'correction_type' => 'negative',
            'category' => 'beda_tetap',
            'amount' => 10000000,
        ]);

        $calc = $this->taxService->calculateTax($this->year);

        $this->assertEquals(40000000, $calc['commercial_profit']);
        $this->assertEquals(10000000, $calc['total_negative_correction']);
        $this->assertEquals(30000000, $calc['fiscal_profit']);
        $this->assertEquals(30000000, $calc['taxable_income']);
        $this->assertEquals(6600000, $calc['tax_amount']); // 30M × 22%
    }

    /** @test */
    public function service_calculates_tax_with_both_corrections()
    {
        $this->createRevenue(100000000);
        $this->createExpense(60000000);

        FiscalCorrection::create([
            'year' => $this->year, 'description' => 'Denda', 'correction_type' => 'positive',
            'category' => 'beda_tetap', 'amount' => 5000000,
        ]);
        FiscalCorrection::create([
            'year' => $this->year, 'description' => 'Hibah', 'correction_type' => 'negative',
            'category' => 'beda_tetap', 'amount' => 3000000,
        ]);

        $calc = $this->taxService->calculateTax($this->year);

        // Fiscal = 40M + 5M - 3M = 42M
        $this->assertEquals(42000000, $calc['fiscal_profit']);
        $this->assertEquals(42000000, $calc['taxable_income']);
        $this->assertEquals(9240000, $calc['tax_amount']); // 42M × 22%
    }

    /** @test */
    public function service_calculates_tax_with_custom_rate()
    {
        $this->createRevenue(100000000);
        $this->createExpense(60000000);

        $calc = $this->taxService->calculateTax($this->year, 25.00);
        // 40M × 25% = 10M
        $this->assertEquals(10000000, $calc['tax_amount']);
        $this->assertEquals(25.00, $calc['tax_rate']);
    }

    // ======================================
    // TaxService: Loss Compensation
    // ======================================

    /** @test */
    public function service_applies_loss_compensation()
    {
        $this->createRevenue(100000000);
        $this->createExpense(60000000);

        LossCompensation::create([
            'source_year' => $this->year - 2,
            'original_amount' => 15000000,
            'used_amount' => 0,
            'remaining_amount' => 15000000,
            'expires_year' => $this->year + 3,
        ]);

        $calc = $this->taxService->calculateTax($this->year);

        $this->assertEquals(40000000, $calc['fiscal_profit']);
        $this->assertEquals(15000000, $calc['loss_compensation_amount']);
        $this->assertEquals(25000000, $calc['taxable_income']); // 40M - 15M
        $this->assertEquals(5500000, $calc['tax_amount']); // 25M × 22%
    }

    /** @test */
    public function loss_compensation_capped_at_fiscal_profit()
    {
        $this->createRevenue(100000000);
        $this->createExpense(80000000); // Profit only 20M

        LossCompensation::create([
            'source_year' => $this->year - 1,
            'original_amount' => 50000000, // 50M loss (more than profit)
            'used_amount' => 0,
            'remaining_amount' => 50000000,
            'expires_year' => $this->year + 4,
        ]);

        $calc = $this->taxService->calculateTax($this->year);

        $this->assertEquals(20000000, $calc['fiscal_profit']);
        $this->assertEquals(20000000, $calc['loss_compensation_amount']); // capped at fiscal profit
        $this->assertEquals(0, $calc['taxable_income']);
        $this->assertEquals(0, $calc['tax_amount']);
    }

    /** @test */
    public function expired_loss_compensation_not_applied()
    {
        $this->createRevenue(100000000);
        $this->createExpense(60000000);

        LossCompensation::create([
            'source_year' => $this->year - 6, // 6 years ago
            'original_amount' => 10000000,
            'used_amount' => 0,
            'remaining_amount' => 10000000,
            'expires_year' => $this->year - 1, // expired last year
        ]);

        $calc = $this->taxService->calculateTax($this->year);

        $this->assertEquals(0, $calc['loss_compensation_amount']);
        $this->assertEquals(40000000, $calc['taxable_income']);
    }

    /** @test */
    public function no_loss_compensation_when_fiscal_loss()
    {
        $this->createRevenue(50000000);
        $this->createExpense(70000000); // Loss

        LossCompensation::create([
            'source_year' => $this->year - 1,
            'original_amount' => 5000000,
            'used_amount' => 0,
            'remaining_amount' => 5000000,
            'expires_year' => $this->year + 4,
        ]);

        $calc = $this->taxService->calculateTax($this->year);

        $this->assertEquals(-20000000, $calc['fiscal_profit']);
        $this->assertEquals(0, $calc['loss_compensation_amount']); // no compensation when loss
        $this->assertEquals(0, $calc['taxable_income']);
        $this->assertEquals(0, $calc['tax_amount']);
    }

    // ======================================
    // TaxService: Save & Journal
    // ======================================

    /** @test */
    public function service_saves_tax_calculation()
    {
        $this->createRevenue(100000000);
        $this->createExpense(60000000);

        $taxCalc = $this->taxService->saveTaxCalculation($this->year);

        $this->assertDatabaseHas('tax_calculations', [
            'year' => $this->year,
            'commercial_profit' => 40000000,
            'fiscal_profit' => 40000000,
            'taxable_income' => 40000000,
            'tax_amount' => 8800000,
            'status' => 'draft',
        ]);
        $this->assertNull($taxCalc->finalized_at);
    }

    /** @test */
    public function service_generates_tax_journal()
    {
        $this->createRevenue(100000000);
        $this->createExpense(60000000);

        $this->taxService->saveTaxCalculation($this->year);
        $journal = $this->taxService->generateTaxJournal($this->year, '5901', '2201');

        $this->assertNotNull($journal);
        $this->assertEquals('tax', $journal->type);
        $this->assertEquals('posted', $journal->status);
        $this->assertStringStartsWith('TAX/', $journal->journal_no);
        $this->assertEquals("{$this->year}-12-31", $journal->journal_date->format('Y-m-d'));
        $this->assertEquals(8800000, $journal->total_debit);
        $this->assertEquals(8800000, $journal->total_credit);

        // Verify journal lines
        $journal->load('journals.coa');
        $debitLine = $journal->journals->where('debit', '>', 0)->first();
        $creditLine = $journal->journals->where('credit', '>', 0)->first();
        $this->assertEquals('5901', $debitLine->coa->code);
        $this->assertEquals('2201', $creditLine->coa->code);
    }

    /** @test */
    public function cannot_generate_duplicate_tax_journal()
    {
        $this->createRevenue(100000000);
        $this->createExpense(60000000);

        $this->taxService->saveTaxCalculation($this->year);
        $this->taxService->generateTaxJournal($this->year, '5901', '2201');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Jurnal pajak untuk tahun ini sudah dibuat.');
        $this->taxService->generateTaxJournal($this->year, '5901', '2201');
    }

    // ======================================
    // TaxService: Finalize
    // ======================================

    /** @test */
    public function service_finalizes_tax_calculation()
    {
        $this->createRevenue(100000000);
        $this->createExpense(60000000);

        LossCompensation::create([
            'source_year' => $this->year - 1,
            'original_amount' => 10000000,
            'used_amount' => 0,
            'remaining_amount' => 10000000,
            'expires_year' => $this->year + 4,
        ]);

        $this->taxService->saveTaxCalculation($this->year);
        $this->taxService->generateTaxJournal($this->year, '5901', '2201');
        $result = $this->taxService->finalizeTaxCalculation($this->year);

        $this->assertEquals('finalized', $result->status);
        $this->assertNotNull($result->finalized_at);

        // Loss compensation should have been applied
        $loss = LossCompensation::first();
        $this->assertEquals(10000000, $loss->used_amount);
        $this->assertEquals(0, $loss->remaining_amount);
    }

    /** @test */
    public function finalize_creates_loss_compensation_on_fiscal_loss()
    {
        $this->createRevenue(50000000);
        $this->createExpense(70000000); // Net loss 20M

        $this->taxService->saveTaxCalculation($this->year);

        // Finalize even without journal (tax amount is 0)
        $taxCalc = TaxCalculation::forYear($this->year)->first();
        $taxCalc->update(['id_journal_master' => null]); // no journal needed for 0 tax
        $result = $this->taxService->finalizeTaxCalculation($this->year);

        $lossComp = LossCompensation::where('source_year', $this->year)->first();
        $this->assertNotNull($lossComp);
        $this->assertEquals(20000000, $lossComp->original_amount);
        $this->assertEquals(0, $lossComp->used_amount);
        $this->assertEquals(20000000, $lossComp->remaining_amount);
        $this->assertEquals($this->year + 5, $lossComp->expires_year);
    }

    /** @test */
    public function cannot_finalize_twice()
    {
        $this->createRevenue(100000000);
        $this->createExpense(60000000);

        $this->taxService->saveTaxCalculation($this->year);
        $this->taxService->generateTaxJournal($this->year, '5901', '2201');
        $this->taxService->finalizeTaxCalculation($this->year);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Perhitungan pajak sudah difinalisasi.');
        $this->taxService->finalizeTaxCalculation($this->year);
    }

    // ======================================
    // Livewire: Calculate & Display
    // ======================================

    /** @test */
    public function component_shows_calculation_results()
    {
        $this->createRevenue(100000000);
        $this->createExpense(60000000);

        FiscalCorrection::create([
            'year' => $this->year, 'description' => 'Denda Pajak',
            'correction_type' => 'positive', 'category' => 'beda_tetap', 'amount' => 5000000,
        ]);

        Livewire::test(TaxCalculationIndex::class)
            ->call('calculateTax')
            ->assertSee('LABA/RUGI KOMERSIAL')
            ->assertSee('KOREKSI FISKAL')
            ->assertSee('LABA/RUGI FISKAL')
            ->assertSee('PENGHASILAN KENA PAJAK')
            ->assertSee('PAJAK PENGHASILAN TERUTANG')
            ->assertSee('LABA BERSIH SETELAH PAJAK');
    }

    /** @test */
    public function component_can_save_calculation()
    {
        $this->createRevenue(100000000);
        $this->createExpense(60000000);

        Livewire::test(TaxCalculationIndex::class)
            ->call('calculateTax')
            ->call('saveTaxCalculation');

        $this->assertDatabaseHas('tax_calculations', [
            'year' => $this->year,
            'status' => 'draft',
        ]);
    }

    // ======================================
    // Model Tests
    // ======================================

    /** @test */
    public function tax_calculation_model_scopes()
    {
        TaxCalculation::create([
            'year' => $this->year,
            'commercial_profit' => 40000000,
            'total_positive_correction' => 0,
            'total_negative_correction' => 0,
            'fiscal_profit' => 40000000,
            'loss_compensation_amount' => 0,
            'taxable_income' => 40000000,
            'tax_rate' => 22.00,
            'tax_amount' => 8800000,
            'status' => 'draft',
        ]);

        $this->assertEquals(1, TaxCalculation::forYear($this->year)->count());
        $this->assertEquals(1, TaxCalculation::draft()->count());
        $this->assertEquals(0, TaxCalculation::finalized()->count());
    }

    /** @test */
    public function loss_compensation_model_available_scope()
    {
        LossCompensation::create([
            'source_year' => $this->year - 2,
            'original_amount' => 10000000,
            'used_amount' => 0,
            'remaining_amount' => 10000000,
            'expires_year' => $this->year + 3,
        ]);
        LossCompensation::create([
            'source_year' => $this->year - 6,
            'original_amount' => 5000000,
            'used_amount' => 0,
            'remaining_amount' => 5000000,
            'expires_year' => $this->year - 1, // expired
        ]);
        LossCompensation::create([
            'source_year' => $this->year - 1,
            'original_amount' => 8000000,
            'used_amount' => 8000000,
            'remaining_amount' => 0, // fully used
            'expires_year' => $this->year + 4,
        ]);

        $available = LossCompensation::available($this->year)->get();
        $this->assertCount(1, $available);
        $this->assertEquals($this->year - 2, $available->first()->source_year);
    }

    /** @test */
    public function loss_compensation_apply_compensation()
    {
        $loss = LossCompensation::create([
            'source_year' => $this->year - 1,
            'original_amount' => 10000000,
            'used_amount' => 0,
            'remaining_amount' => 10000000,
            'expires_year' => $this->year + 4,
        ]);

        $applied = $loss->applyCompensation(3000000);
        $this->assertEquals(3000000, $applied);
        $this->assertEquals(3000000, $loss->fresh()->used_amount);
        $this->assertEquals(7000000, $loss->fresh()->remaining_amount);

        // Apply more than remaining
        $applied2 = $loss->applyCompensation(20000000);
        $this->assertEquals(7000000, $applied2); // capped at remaining
        $this->assertEquals(10000000, $loss->fresh()->used_amount);
        $this->assertEquals(0, $loss->fresh()->remaining_amount);
    }

    /** @test */
    public function net_income_calculated_as_commercial_minus_tax()
    {
        $this->createRevenue(100000000);
        $this->createExpense(60000000);

        $calc = $this->taxService->calculateTax($this->year);
        // net_income = commercial_profit - tax_amount = 40M - 8.8M = 31.2M
        $this->assertEquals(31200000, $calc['net_income']);
    }
}
