<?php

namespace Tests\Feature;

use App\Livewire\IncomeStatement\IncomeStatementIndex;
use App\Models\COA;
use App\Models\Period;
use App\Models\User;
use App\Services\JournalService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class IncomeStatementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Period $currentPeriod;
    protected JournalService $journalService;
    protected COA $cashAccount;
    protected COA $capitalAccount;
    protected COA $revenueAccount;
    protected COA $revServiceAccount;
    protected COA $expenseAccount;
    protected COA $expenseRentAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->journalService = new JournalService();

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

        $this->cashAccount = COA::create([
            'code' => '1101', 'name' => 'Kas', 'type' => 'aktiva',
            'level' => 2, 'order' => 1, 'is_active' => true, 'is_leaf_account' => true,
        ]);
        $this->capitalAccount = COA::create([
            'code' => '3101', 'name' => 'Modal', 'type' => 'modal',
            'level' => 2, 'order' => 1, 'is_active' => true, 'is_leaf_account' => true,
        ]);
        $this->revenueAccount = COA::create([
            'code' => '4101', 'name' => 'Pendapatan Penjualan', 'type' => 'pendapatan',
            'level' => 2, 'order' => 1, 'is_active' => true, 'is_leaf_account' => true,
        ]);
        $this->revServiceAccount = COA::create([
            'code' => '4102', 'name' => 'Pendapatan Jasa', 'type' => 'pendapatan',
            'level' => 2, 'order' => 2, 'is_active' => true, 'is_leaf_account' => true,
        ]);
        $this->expenseAccount = COA::create([
            'code' => '5101', 'name' => 'Beban Gaji', 'type' => 'beban',
            'level' => 2, 'order' => 1, 'is_active' => true, 'is_leaf_account' => true,
        ]);
        $this->expenseRentAccount = COA::create([
            'code' => '5102', 'name' => 'Beban Sewa', 'type' => 'beban',
            'level' => 2, 'order' => 2, 'is_active' => true, 'is_leaf_account' => true,
        ]);
    }

    private function seedProfitScenario(): void
    {
        // Revenue: 5M penjualan + 2M jasa = 7M
        $this->journalService->createJournalEntry([
            'journal_date' => now()->format('Y-m-d'),
            'id_period' => $this->currentPeriod->id,
            'status' => 'posted',
            'entries' => [
                ['coa_code' => '1101', 'debit' => 5000000, 'credit' => 0],
                ['coa_code' => '4101', 'debit' => 0, 'credit' => 5000000],
            ],
        ]);

        $this->journalService->createJournalEntry([
            'journal_date' => now()->format('Y-m-d'),
            'id_period' => $this->currentPeriod->id,
            'status' => 'posted',
            'entries' => [
                ['coa_code' => '1101', 'debit' => 2000000, 'credit' => 0],
                ['coa_code' => '4102', 'debit' => 0, 'credit' => 2000000],
            ],
        ]);

        // Expense: 3M gaji + 1M sewa = 4M
        $this->journalService->createJournalEntry([
            'journal_date' => now()->format('Y-m-d'),
            'id_period' => $this->currentPeriod->id,
            'status' => 'posted',
            'entries' => [
                ['coa_code' => '5101', 'debit' => 3000000, 'credit' => 0],
                ['coa_code' => '1101', 'debit' => 0, 'credit' => 3000000],
            ],
        ]);

        $this->journalService->createJournalEntry([
            'journal_date' => now()->format('Y-m-d'),
            'id_period' => $this->currentPeriod->id,
            'status' => 'posted',
            'entries' => [
                ['coa_code' => '5102', 'debit' => 1000000, 'credit' => 0],
                ['coa_code' => '1101', 'debit' => 0, 'credit' => 1000000],
            ],
        ]);
    }

    private function seedLossScenario(): void
    {
        // Revenue: 1M
        $this->journalService->createJournalEntry([
            'journal_date' => now()->format('Y-m-d'),
            'id_period' => $this->currentPeriod->id,
            'status' => 'posted',
            'entries' => [
                ['coa_code' => '1101', 'debit' => 1000000, 'credit' => 0],
                ['coa_code' => '4101', 'debit' => 0, 'credit' => 1000000],
            ],
        ]);

        // Expense: 5M
        $this->journalService->createJournalEntry([
            'journal_date' => now()->format('Y-m-d'),
            'id_period' => $this->currentPeriod->id,
            'status' => 'posted',
            'entries' => [
                ['coa_code' => '5101', 'debit' => 5000000, 'credit' => 0],
                ['coa_code' => '1101', 'debit' => 0, 'credit' => 5000000],
            ],
        ]);
    }

    // ==========================================
    // Route & Page Tests
    // ==========================================

    public function test_income_statement_page_is_accessible(): void
    {
        $response = $this->get(route('income-statement'));
        $response->assertStatus(200);
    }

    public function test_income_statement_page_requires_auth(): void
    {
        auth()->logout();
        $response = $this->get(route('income-statement'));
        $response->assertRedirect();
    }

    // ==========================================
    // Component Render Tests
    // ==========================================

    public function test_component_renders_successfully(): void
    {
        Livewire::test(IncomeStatementIndex::class)
            ->assertStatus(200)
            ->assertSee('laporan laba rugi');
    }

    public function test_component_shows_period_filter(): void
    {
        Livewire::test(IncomeStatementIndex::class)
            ->assertSee($this->currentPeriod->name);
    }

    public function test_component_hides_report_initially(): void
    {
        Livewire::test(IncomeStatementIndex::class)
            ->assertSet('showReport', false);
    }

    // ==========================================
    // Generate Report Tests
    // ==========================================

    public function test_generate_report_requires_filter(): void
    {
        Livewire::test(IncomeStatementIndex::class)
            ->call('generateReport')
            ->assertSet('showReport', false)
            ->assertDispatched('alert');
    }

    public function test_generate_report_with_period(): void
    {
        $this->seedProfitScenario();

        Livewire::test(IncomeStatementIndex::class)
            ->set('filterPeriod', $this->currentPeriod->id)
            ->call('generateReport')
            ->assertSet('showReport', true);
    }

    public function test_generate_report_with_date_range(): void
    {
        $this->seedProfitScenario();

        Livewire::test(IncomeStatementIndex::class)
            ->set('dateFrom', now()->startOfMonth()->format('Y-m-d'))
            ->set('dateTo', now()->endOfMonth()->format('Y-m-d'))
            ->call('generateReport')
            ->assertSet('showReport', true);
    }

    // ==========================================
    // Profit Scenario Tests
    // ==========================================

    public function test_report_shows_pendapatan_accounts(): void
    {
        $this->seedProfitScenario();

        Livewire::test(IncomeStatementIndex::class)
            ->set('filterPeriod', $this->currentPeriod->id)
            ->call('generateReport')
            ->assertSee('Pendapatan Penjualan')
            ->assertSee('Pendapatan Jasa');
    }

    public function test_report_shows_beban_accounts(): void
    {
        $this->seedProfitScenario();

        Livewire::test(IncomeStatementIndex::class)
            ->set('filterPeriod', $this->currentPeriod->id)
            ->call('generateReport')
            ->assertSee('Beban Gaji')
            ->assertSee('Beban Sewa');
    }

    public function test_report_shows_profit_indicator(): void
    {
        $this->seedProfitScenario();

        // Net = 7M - 4M = 3M (laba)
        $component = Livewire::test(IncomeStatementIndex::class)
            ->set('filterPeriod', $this->currentPeriod->id)
            ->call('generateReport');

        $component->assertSee('Pendapatan Penjualan')
            ->assertSee('Pendapatan Jasa');
        $component->assertSee('LABA BERSIH');
    }

    // ==========================================
    // Loss Scenario Tests
    // ==========================================

    public function test_report_shows_loss_indicator(): void
    {
        $this->seedLossScenario();

        // Net = 1M - 5M = -4M (rugi)
        $component = Livewire::test(IncomeStatementIndex::class)
            ->set('filterPeriod', $this->currentPeriod->id)
            ->call('generateReport');

        $component->assertSee('RUGI BERSIH');
    }

    // ==========================================
    // Clear Filters
    // ==========================================

    public function test_clear_filters_resets_state(): void
    {
        Livewire::test(IncomeStatementIndex::class)
            ->set('filterPeriod', $this->currentPeriod->id)
            ->set('dateFrom', '2026-01-01')
            ->set('dateTo', '2026-01-31')
            ->set('showReport', true)
            ->call('clearFilters')
            ->assertSet('filterPeriod', '')
            ->assertSet('dateFrom', '')
            ->assertSet('dateTo', '')
            ->assertSet('showReport', false);
    }

    // ==========================================
    // Edge Cases
    // ==========================================

    public function test_empty_report_when_no_posted_journals(): void
    {
        $this->journalService->createJournalEntry([
            'journal_date' => now()->format('Y-m-d'),
            'id_period' => $this->currentPeriod->id,
            'entries' => [
                ['coa_code' => '1101', 'debit' => 1000000, 'credit' => 0],
                ['coa_code' => '4101', 'debit' => 0, 'credit' => 1000000],
            ],
        ]);

        Livewire::test(IncomeStatementIndex::class)
            ->set('filterPeriod', $this->currentPeriod->id)
            ->call('generateReport')
            ->assertSet('showReport', true);
    }

    public function test_only_pendapatan_no_beban(): void
    {
        $this->journalService->createJournalEntry([
            'journal_date' => now()->format('Y-m-d'),
            'id_period' => $this->currentPeriod->id,
            'status' => 'posted',
            'entries' => [
                ['coa_code' => '1101', 'debit' => 5000000, 'credit' => 0],
                ['coa_code' => '4101', 'debit' => 0, 'credit' => 5000000],
            ],
        ]);

        Livewire::test(IncomeStatementIndex::class)
            ->set('filterPeriod', $this->currentPeriod->id)
            ->call('generateReport')
            ->assertSee('Pendapatan Penjualan')
            ->assertSee('Laba Bersih');
    }

    public function test_only_beban_no_pendapatan(): void
    {
        // Initial capital to offset
        $this->journalService->createJournalEntry([
            'journal_date' => now()->format('Y-m-d'),
            'id_period' => $this->currentPeriod->id,
            'status' => 'posted',
            'entries' => [
                ['coa_code' => '1101', 'debit' => 10000000, 'credit' => 0],
                ['coa_code' => '3101', 'debit' => 0, 'credit' => 10000000],
            ],
        ]);

        $this->journalService->createJournalEntry([
            'journal_date' => now()->format('Y-m-d'),
            'id_period' => $this->currentPeriod->id,
            'status' => 'posted',
            'entries' => [
                ['coa_code' => '5101', 'debit' => 2000000, 'credit' => 0],
                ['coa_code' => '1101', 'debit' => 0, 'credit' => 2000000],
            ],
        ]);

        Livewire::test(IncomeStatementIndex::class)
            ->set('filterPeriod', $this->currentPeriod->id)
            ->call('generateReport')
            ->assertSee('Beban Gaji')
            ->assertSee('Rugi Bersih');
    }
}
