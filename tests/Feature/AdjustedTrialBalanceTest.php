<?php

namespace Tests\Feature;

use App\Livewire\AdjustedTrialBalance\AdjustedTrialBalanceIndex;
use App\Models\COA;
use App\Models\Period;
use App\Models\User;
use App\Services\FinancialReportService;
use App\Services\JournalService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdjustedTrialBalanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Period $currentPeriod;
    protected Period $prevPeriod;
    protected JournalService $journalService;
    protected FinancialReportService $reportService;

    protected COA $cashAccount;
    protected COA $receivableAccount;
    protected COA $equipmentAccount;
    protected COA $accDepreciationAccount;
    protected COA $payableAccount;
    protected COA $capitalAccount;
    protected COA $revenueAccount;
    protected COA $expenseAccount;
    protected COA $depreciationExpense;

    protected function setUp(): void
    {
        parent::setUp();

        $this->journalService = new JournalService();
        $this->reportService = new FinancialReportService();

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

        $prevMonth = $now->copy()->subMonth();
        $this->prevPeriod = Period::create([
            'code' => $prevMonth->format('Ym'),
            'name' => $prevMonth->translatedFormat('F') . ' ' . $prevMonth->year,
            'start_date' => $prevMonth->copy()->startOfMonth()->format('Y-m-d'),
            'end_date' => $prevMonth->copy()->endOfMonth()->format('Y-m-d'),
            'year' => $prevMonth->year,
            'month' => $prevMonth->month,
            'is_active' => true,
            'is_closed' => false,
        ]);

        $this->cashAccount = COA::create([
            'code' => '1101', 'name' => 'Kas', 'type' => 'aktiva',
            'level' => 2, 'order' => 1, 'is_active' => true, 'is_leaf_account' => true,
        ]);
        $this->receivableAccount = COA::create([
            'code' => '1201', 'name' => 'Piutang Dagang', 'type' => 'aktiva',
            'level' => 2, 'order' => 2, 'is_active' => true, 'is_leaf_account' => true,
        ]);
        $this->equipmentAccount = COA::create([
            'code' => '1301', 'name' => 'Peralatan', 'type' => 'aktiva',
            'level' => 2, 'order' => 3, 'is_active' => true, 'is_leaf_account' => true,
        ]);
        $this->accDepreciationAccount = COA::create([
            'code' => '1309', 'name' => 'Akumulasi Penyusutan', 'type' => 'aktiva',
            'level' => 2, 'order' => 4, 'is_active' => true, 'is_leaf_account' => true,
        ]);
        $this->payableAccount = COA::create([
            'code' => '2101', 'name' => 'Hutang Dagang', 'type' => 'pasiva',
            'level' => 2, 'order' => 1, 'is_active' => true, 'is_leaf_account' => true,
        ]);
        $this->capitalAccount = COA::create([
            'code' => '3101', 'name' => 'Modal Pemilik', 'type' => 'modal',
            'level' => 2, 'order' => 1, 'is_active' => true, 'is_leaf_account' => true,
        ]);
        $this->revenueAccount = COA::create([
            'code' => '4101', 'name' => 'Pendapatan', 'type' => 'pendapatan',
            'level' => 2, 'order' => 1, 'is_active' => true, 'is_leaf_account' => true,
        ]);
        $this->expenseAccount = COA::create([
            'code' => '5101', 'name' => 'Beban Gaji', 'type' => 'beban',
            'level' => 2, 'order' => 1, 'is_active' => true, 'is_leaf_account' => true,
        ]);
        $this->depreciationExpense = COA::create([
            'code' => '5201', 'name' => 'Beban Penyusutan', 'type' => 'beban',
            'level' => 2, 'order' => 2, 'is_active' => true, 'is_leaf_account' => true,
        ]);
    }

    // ======================================
    // Helpers
    // ======================================

    private function createPostedGeneralJournal(array $entries, ?int $periodId = null, ?string $date = null)
    {
        return $this->journalService->createJournalEntry([
            'journal_date' => $date ?? now()->format('Y-m-d'),
            'id_period' => $periodId ?? $this->currentPeriod->id,
            'status' => 'posted',
            'type' => 'general',
            'entries' => $entries,
        ]);
    }

    private function createPostedAdjustmentJournal(array $entries, ?int $periodId = null, ?string $date = null)
    {
        return $this->journalService->createJournalEntry([
            'journal_date' => $date ?? now()->format('Y-m-d'),
            'id_period' => $periodId ?? $this->currentPeriod->id,
            'status' => 'posted',
            'type' => 'adjustment',
            'entries' => $entries,
        ]);
    }

    private function seedGeneralTransactions(): void
    {
        // Owner invests 10M cash
        $this->createPostedGeneralJournal([
            ['coa_code' => '1101', 'debit' => 10000000, 'credit' => 0],
            ['coa_code' => '3101', 'debit' => 0, 'credit' => 10000000],
        ]);

        // Buy equipment 3M
        $this->createPostedGeneralJournal([
            ['coa_code' => '1301', 'debit' => 3000000, 'credit' => 0],
            ['coa_code' => '1101', 'debit' => 0, 'credit' => 3000000],
        ]);

        // Revenue 5M
        $this->createPostedGeneralJournal([
            ['coa_code' => '1101', 'debit' => 5000000, 'credit' => 0],
            ['coa_code' => '4101', 'debit' => 0, 'credit' => 5000000],
        ]);

        // Salary expense 2M
        $this->createPostedGeneralJournal([
            ['coa_code' => '5101', 'debit' => 2000000, 'credit' => 0],
            ['coa_code' => '1101', 'debit' => 0, 'credit' => 2000000],
        ]);
    }

    private function seedAdjustmentTransactions(): void
    {
        // Depreciation adjustment: 500K
        $this->createPostedAdjustmentJournal([
            ['coa_code' => '5201', 'debit' => 500000, 'credit' => 0],
            ['coa_code' => '1309', 'debit' => 0, 'credit' => 500000],
        ]);
    }

    // ======================================
    // Route & Page Tests
    // ======================================

    public function test_page_requires_authentication(): void
    {
        auth()->logout();
        $this->get(route('adjusted-trial-balance'))->assertRedirect(route('login'));
    }

    public function test_page_accessible_for_authenticated_user(): void
    {
        $this->get(route('adjusted-trial-balance'))->assertStatus(200);
    }

    public function test_page_contains_livewire_component(): void
    {
        $this->get(route('adjusted-trial-balance'))
            ->assertSeeLivewire('adjusted-trial-balance.adjusted-trial-balance-index');
    }

    public function test_page_shows_title(): void
    {
        $this->get(route('adjusted-trial-balance'))
            ->assertSee('Neraca Penyesuaian');
    }

    // ======================================
    // Component Rendering
    // ======================================

    public function test_component_renders(): void
    {
        Livewire::test(AdjustedTrialBalanceIndex::class)
            ->assertStatus(200);
    }

    public function test_component_shows_empty_state_initially(): void
    {
        Livewire::test(AdjustedTrialBalanceIndex::class)
            ->assertSet('showReport', false)
            ->assertSee('Pilih Periode atau Range Tanggal');
    }

    public function test_generate_report_requires_filter(): void
    {
        Livewire::test(AdjustedTrialBalanceIndex::class)
            ->call('generateReport')
            ->assertSet('showReport', false);
    }

    public function test_generate_report_with_period(): void
    {
        Livewire::test(AdjustedTrialBalanceIndex::class)
            ->set('filterPeriod', $this->currentPeriod->id)
            ->call('generateReport')
            ->assertSet('showReport', true);
    }

    public function test_generate_report_with_date_range(): void
    {
        Livewire::test(AdjustedTrialBalanceIndex::class)
            ->set('dateFrom', now()->startOfMonth()->format('Y-m-d'))
            ->set('dateTo', now()->endOfMonth()->format('Y-m-d'))
            ->call('generateReport')
            ->assertSet('showReport', true);
    }

    public function test_clear_filters_resets_state(): void
    {
        Livewire::test(AdjustedTrialBalanceIndex::class)
            ->set('filterPeriod', $this->currentPeriod->id)
            ->set('dateFrom', '2026-01-01')
            ->set('dateTo', '2026-12-31')
            ->call('generateReport')
            ->assertSet('showReport', true)
            ->call('clearFilters')
            ->assertSet('filterPeriod', '')
            ->assertSet('dateFrom', '')
            ->assertSet('dateTo', '')
            ->assertSet('showReport', false);
    }

    // ======================================
    // Service: getAdjustedTrialBalance
    // ======================================

    public function test_service_returns_empty_when_no_data(): void
    {
        $result = $this->reportService->getAdjustedTrialBalance();

        $this->assertCount(0, $result['accounts']);
        $this->assertEquals(0, $result['total_ns_debit']);
        $this->assertEquals(0, $result['total_ns_credit']);
        $this->assertEquals(0, $result['total_adj_debit']);
        $this->assertEquals(0, $result['total_adj_credit']);
        $this->assertEquals(0, $result['total_nsd_debit']);
        $this->assertEquals(0, $result['total_nsd_credit']);
    }

    public function test_service_shows_general_journals_in_ns_columns(): void
    {
        $this->seedGeneralTransactions();

        $result = $this->reportService->getAdjustedTrialBalance();

        // Kas: 10M + 5M - 3M - 2M = 10M debit
        $cash = $result['accounts']->firstWhere('coa_code', '1101');
        $this->assertEquals(10000000, $cash->ns_debit);
        $this->assertEquals(0, $cash->ns_credit);
        $this->assertEquals(0, $cash->adj_debit);
        $this->assertEquals(0, $cash->adj_credit);
    }

    public function test_service_shows_adjustment_journals_in_adj_columns(): void
    {
        $this->seedGeneralTransactions();
        $this->seedAdjustmentTransactions();

        $result = $this->reportService->getAdjustedTrialBalance();

        // Beban Penyusutan: only from adjustment
        $depExp = $result['accounts']->firstWhere('coa_code', '5201');
        $this->assertEquals(0, $depExp->ns_debit);
        $this->assertEquals(0, $depExp->ns_credit);
        $this->assertEquals(500000, $depExp->adj_debit);
        $this->assertEquals(0, $depExp->adj_credit);
    }

    public function test_service_calculates_adjusted_trial_balance_correctly(): void
    {
        $this->seedGeneralTransactions();
        $this->seedAdjustmentTransactions();

        $result = $this->reportService->getAdjustedTrialBalance();

        // Kas: NS=10M, ADJ=0, NSD=10M
        $cash = $result['accounts']->firstWhere('coa_code', '1101');
        $this->assertEquals(10000000, $cash->nsd_debit);
        $this->assertEquals(0, $cash->nsd_credit);

        // Peralatan: NS=3M, ADJ=0, NSD=3M
        $equip = $result['accounts']->firstWhere('coa_code', '1301');
        $this->assertEquals(3000000, $equip->nsd_debit);
        $this->assertEquals(0, $equip->nsd_credit);

        // Akumulasi Penyusutan: NS=0, ADJ=500K credit => NSD should show 500K credit
        // (aktiva contra account: debit-normal, balance = 0 - 500K = -500K → show in credit)
        $accDep = $result['accounts']->firstWhere('coa_code', '1309');
        $this->assertEquals(0, $accDep->nsd_debit);
        $this->assertEquals(500000, $accDep->nsd_credit);

        // Modal: NS=10M credit, ADJ=0, NSD=10M credit
        $capital = $result['accounts']->firstWhere('coa_code', '3101');
        $this->assertEquals(0, $capital->nsd_debit);
        $this->assertEquals(10000000, $capital->nsd_credit);

        // Pendapatan: NS=5M credit, ADJ=0, NSD=5M credit
        $revenue = $result['accounts']->firstWhere('coa_code', '4101');
        $this->assertEquals(0, $revenue->nsd_debit);
        $this->assertEquals(5000000, $revenue->nsd_credit);

        // Beban Gaji: NS=2M debit, ADJ=0, NSD=2M debit
        $salary = $result['accounts']->firstWhere('coa_code', '5101');
        $this->assertEquals(2000000, $salary->nsd_debit);
        $this->assertEquals(0, $salary->nsd_credit);

        // Beban Penyusutan: NS=0, ADJ=500K debit, NSD=500K debit
        $depExp = $result['accounts']->firstWhere('coa_code', '5201');
        $this->assertEquals(500000, $depExp->nsd_debit);
        $this->assertEquals(0, $depExp->nsd_credit);
    }

    public function test_service_ns_columns_are_balanced(): void
    {
        $this->seedGeneralTransactions();

        $result = $this->reportService->getAdjustedTrialBalance();

        $this->assertTrue($result['ns_balanced']);
        $this->assertEquals($result['total_ns_debit'], $result['total_ns_credit']);
    }

    public function test_service_adj_columns_are_balanced(): void
    {
        $this->seedGeneralTransactions();
        $this->seedAdjustmentTransactions();

        $result = $this->reportService->getAdjustedTrialBalance();

        $this->assertTrue($result['adj_balanced']);
        $this->assertEquals($result['total_adj_debit'], $result['total_adj_credit']);
    }

    public function test_service_nsd_columns_are_balanced(): void
    {
        $this->seedGeneralTransactions();
        $this->seedAdjustmentTransactions();

        $result = $this->reportService->getAdjustedTrialBalance();

        $this->assertTrue($result['nsd_balanced']);
        $this->assertEquals($result['total_nsd_debit'], $result['total_nsd_credit']);
    }

    public function test_service_ignores_draft_journals(): void
    {
        // Create a posted general journal
        $this->createPostedGeneralJournal([
            ['coa_code' => '1101', 'debit' => 5000000, 'credit' => 0],
            ['coa_code' => '3101', 'debit' => 0, 'credit' => 5000000],
        ]);

        // Create a draft adjustment journal (should be ignored)
        $this->journalService->createJournalEntry([
            'journal_date' => now()->format('Y-m-d'),
            'id_period' => $this->currentPeriod->id,
            'status' => 'draft',
            'type' => 'adjustment',
            'entries' => [
                ['coa_code' => '5201', 'debit' => 300000, 'credit' => 0],
                ['coa_code' => '1309', 'debit' => 0, 'credit' => 300000],
            ],
        ]);

        $result = $this->reportService->getAdjustedTrialBalance();

        // No adjustment should appear
        $this->assertEquals(0, $result['total_adj_debit']);
        $this->assertEquals(0, $result['total_adj_credit']);
    }

    public function test_service_filters_by_period(): void
    {
        // Current period transaction
        $this->createPostedGeneralJournal([
            ['coa_code' => '1101', 'debit' => 5000000, 'credit' => 0],
            ['coa_code' => '3101', 'debit' => 0, 'credit' => 5000000],
        ], $this->currentPeriod->id);

        // Previous period transaction
        $prevDate = now()->subMonth()->format('Y-m-') . '15';
        $this->createPostedGeneralJournal([
            ['coa_code' => '1101', 'debit' => 3000000, 'credit' => 0],
            ['coa_code' => '3101', 'debit' => 0, 'credit' => 3000000],
        ], $this->prevPeriod->id, $prevDate);

        $result = $this->reportService->getAdjustedTrialBalance([
            'period_id' => $this->currentPeriod->id,
        ]);

        $cash = $result['accounts']->firstWhere('coa_code', '1101');
        $this->assertEquals(5000000, $cash->ns_debit);
    }

    public function test_service_filters_by_date_range(): void
    {
        $this->createPostedGeneralJournal([
            ['coa_code' => '1101', 'debit' => 5000000, 'credit' => 0],
            ['coa_code' => '3101', 'debit' => 0, 'credit' => 5000000],
        ], $this->currentPeriod->id, now()->format('Y-m-d'));

        $result = $this->reportService->getAdjustedTrialBalance([
            'date_from' => now()->startOfMonth()->format('Y-m-d'),
            'date_to' => now()->endOfMonth()->format('Y-m-d'),
        ]);

        $this->assertGreaterThan(0, $result['accounts']->count());
    }

    public function test_service_skips_accounts_with_no_activity(): void
    {
        // Only create transactions for cash and capital
        $this->createPostedGeneralJournal([
            ['coa_code' => '1101', 'debit' => 1000000, 'credit' => 0],
            ['coa_code' => '3101', 'debit' => 0, 'credit' => 1000000],
        ]);

        $result = $this->reportService->getAdjustedTrialBalance();

        // Only cash and capital should appear
        $this->assertEquals(2, $result['accounts']->count());
        $this->assertNotNull($result['accounts']->firstWhere('coa_code', '1101'));
        $this->assertNotNull($result['accounts']->firstWhere('coa_code', '3101'));
        $this->assertNull($result['accounts']->firstWhere('coa_code', '5201'));
    }

    public function test_service_multiple_adjustments_accumulate(): void
    {
        $this->seedGeneralTransactions();

        // First adjustment
        $this->createPostedAdjustmentJournal([
            ['coa_code' => '5201', 'debit' => 300000, 'credit' => 0],
            ['coa_code' => '1309', 'debit' => 0, 'credit' => 300000],
        ]);

        // Second adjustment
        $this->createPostedAdjustmentJournal([
            ['coa_code' => '5201', 'debit' => 200000, 'credit' => 0],
            ['coa_code' => '1309', 'debit' => 0, 'credit' => 200000],
        ]);

        $result = $this->reportService->getAdjustedTrialBalance();

        $depExp = $result['accounts']->firstWhere('coa_code', '5201');
        $this->assertEquals(500000, $depExp->adj_debit);

        $accDep = $result['accounts']->firstWhere('coa_code', '1309');
        $this->assertEquals(500000, $accDep->adj_credit);
    }

    // ======================================
    // Component with Report Data
    // ======================================

    public function test_component_shows_balanced_indicators(): void
    {
        $this->seedGeneralTransactions();
        $this->seedAdjustmentTransactions();

        Livewire::test(AdjustedTrialBalanceIndex::class)
            ->set('filterPeriod', $this->currentPeriod->id)
            ->call('generateReport')
            ->assertSee('SEIMBANG');
    }

    public function test_component_shows_worksheet_table(): void
    {
        $this->seedGeneralTransactions();
        $this->seedAdjustmentTransactions();

        Livewire::test(AdjustedTrialBalanceIndex::class)
            ->set('filterPeriod', $this->currentPeriod->id)
            ->call('generateReport')
            ->assertSee('Neraca Saldo')
            ->assertSee('Penyesuaian')
            ->assertSee('NS Disesuaikan');
    }

    public function test_component_shows_account_data(): void
    {
        $this->seedGeneralTransactions();
        $this->seedAdjustmentTransactions();

        Livewire::test(AdjustedTrialBalanceIndex::class)
            ->set('filterPeriod', $this->currentPeriod->id)
            ->call('generateReport')
            ->assertSee('Kas')
            ->assertSee('Beban Penyusutan')
            ->assertSee('Akumulasi Penyusutan');
    }

    public function test_component_shows_no_data_message_when_empty(): void
    {
        Livewire::test(AdjustedTrialBalanceIndex::class)
            ->set('filterPeriod', $this->currentPeriod->id)
            ->call('generateReport')
            ->assertSee('Tidak ada data');
    }
}
