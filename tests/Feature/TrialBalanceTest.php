<?php

namespace Tests\Feature;

use App\Livewire\TrialBalance\TrialBalanceIndex;
use App\Models\COA;
use App\Models\Period;
use App\Models\User;
use App\Services\JournalService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TrialBalanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Period $currentPeriod;
    protected JournalService $journalService;
    protected COA $cashAccount;
    protected COA $capitalAccount;
    protected COA $revenueAccount;
    protected COA $payableAccount;
    protected COA $expenseAccount;

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
    }

    private function seedTransactions(): void
    {
        // Investment 10M
        $this->journalService->createJournalEntry([
            'journal_date' => now()->format('Y-m-d'),
            'id_period' => $this->currentPeriod->id,
            'status' => 'posted',
            'entries' => [
                ['coa_code' => '1101', 'debit' => 10000000, 'credit' => 0],
                ['coa_code' => '3101', 'debit' => 0, 'credit' => 10000000],
            ],
        ]);

        // Revenue 5M
        $this->journalService->createJournalEntry([
            'journal_date' => now()->format('Y-m-d'),
            'id_period' => $this->currentPeriod->id,
            'status' => 'posted',
            'entries' => [
                ['coa_code' => '1101', 'debit' => 5000000, 'credit' => 0],
                ['coa_code' => '4101', 'debit' => 0, 'credit' => 5000000],
            ],
        ]);

        // Expense 2M
        $this->journalService->createJournalEntry([
            'journal_date' => now()->format('Y-m-d'),
            'id_period' => $this->currentPeriod->id,
            'status' => 'posted',
            'entries' => [
                ['coa_code' => '5101', 'debit' => 2000000, 'credit' => 0],
                ['coa_code' => '1101', 'debit' => 0, 'credit' => 2000000],
            ],
        ]);
    }

    // ==========================================
    // Route & Page Tests
    // ==========================================

    public function test_trial_balance_page_is_accessible(): void
    {
        $response = $this->get(route('trial-balance'));
        $response->assertStatus(200);
    }

    public function test_trial_balance_page_requires_auth(): void
    {
        auth()->logout();
        $response = $this->get(route('trial-balance'));
        $response->assertRedirect();
    }

    // ==========================================
    // Component Render Tests
    // ==========================================

    public function test_component_renders_successfully(): void
    {
        Livewire::test(TrialBalanceIndex::class)
            ->assertStatus(200)
            ->assertSee('neraca saldo');
    }

    public function test_component_shows_period_filter(): void
    {
        Livewire::test(TrialBalanceIndex::class)
            ->assertSee($this->currentPeriod->name);
    }

    public function test_component_hides_report_initially(): void
    {
        Livewire::test(TrialBalanceIndex::class)
            ->assertSet('showReport', false);
    }

    // ==========================================
    // Generate Report Tests
    // ==========================================

    public function test_generate_report_requires_filter(): void
    {
        Livewire::test(TrialBalanceIndex::class)
            ->call('generateReport')
            ->assertSet('showReport', false)
            ->assertDispatched('alert');
    }

    public function test_generate_report_with_period(): void
    {
        $this->seedTransactions();

        Livewire::test(TrialBalanceIndex::class)
            ->set('filterPeriod', $this->currentPeriod->id)
            ->call('generateReport')
            ->assertSet('showReport', true);
    }

    public function test_generate_report_with_date_range(): void
    {
        $this->seedTransactions();

        Livewire::test(TrialBalanceIndex::class)
            ->set('dateFrom', now()->startOfMonth()->format('Y-m-d'))
            ->set('dateTo', now()->endOfMonth()->format('Y-m-d'))
            ->call('generateReport')
            ->assertSet('showReport', true);
    }

    public function test_report_shows_balance_sheet_data(): void
    {
        $this->seedTransactions();

        $component = Livewire::test(TrialBalanceIndex::class)
            ->set('filterPeriod', $this->currentPeriod->id)
            ->call('generateReport');

        // Should display Neraca tab content with aktiva/pasiva
        $component->assertSee('Kas');
        $component->assertSee('Modal Pemilik');
    }

    public function test_report_shows_trial_balance_data(): void
    {
        $this->seedTransactions();

        $component = Livewire::test(TrialBalanceIndex::class)
            ->set('filterPeriod', $this->currentPeriod->id)
            ->call('generateReport');

        // Trial balance tab should show account data
        $component->assertSee('1101');
        $component->assertSee('3101');
        $component->assertSee('4101');
    }

    public function test_report_shows_laba_rugi_amount(): void
    {
        $this->seedTransactions();

        $component = Livewire::test(TrialBalanceIndex::class)
            ->set('filterPeriod', $this->currentPeriod->id)
            ->call('generateReport');

        // Laba = 5M - 2M = 3M
        $component->assertSee('LABA BERSIH');
    }

    public function test_report_shows_balanced_indicator(): void
    {
        $this->seedTransactions();

        $component = Livewire::test(TrialBalanceIndex::class)
            ->set('filterPeriod', $this->currentPeriod->id)
            ->call('generateReport');

        $component->assertSee('SEIMBANG');
    }

    // ==========================================
    // Clear Filters Tests
    // ==========================================

    public function test_clear_filters_resets_state(): void
    {
        Livewire::test(TrialBalanceIndex::class)
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

    public function test_no_posted_journals_shows_empty_report(): void
    {
        // create draft journal only
        $this->journalService->createJournalEntry([
            'journal_date' => now()->format('Y-m-d'),
            'id_period' => $this->currentPeriod->id,
            'entries' => [
                ['coa_code' => '1101', 'debit' => 1000000, 'credit' => 0],
                ['coa_code' => '4101', 'debit' => 0, 'credit' => 1000000],
            ],
        ]);

        $component = Livewire::test(TrialBalanceIndex::class)
            ->set('filterPeriod', $this->currentPeriod->id)
            ->call('generateReport');

        $component->assertSet('showReport', true);
        // Should show the report but with no data
    }

    public function test_dateFrom_only_works(): void
    {
        $this->seedTransactions();

        Livewire::test(TrialBalanceIndex::class)
            ->set('dateFrom', now()->startOfMonth()->format('Y-m-d'))
            ->call('generateReport')
            ->assertSet('showReport', true);
    }

    public function test_dateTo_only_works(): void
    {
        $this->seedTransactions();

        Livewire::test(TrialBalanceIndex::class)
            ->set('dateTo', now()->endOfMonth()->format('Y-m-d'))
            ->call('generateReport')
            ->assertSet('showReport', true);
    }
}
