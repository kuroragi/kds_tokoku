<?php

namespace Tests\Feature;

use App\Livewire\GeneralLedger\GeneralLedgerIndex;
use App\Models\COA;
use App\Models\Journal;
use App\Models\JournalMaster;
use App\Models\Period;
use App\Models\User;
use App\Services\JournalService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GeneralLedgerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Period $currentPeriod;
    protected Period $prevPeriod;
    protected COA $cashAccount;
    protected COA $revenueAccount;
    protected COA $expenseAccount;
    protected COA $receivableAccount;
    protected JournalService $journalService;

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
            'code' => '1101', 'name' => 'Kas di Tangan', 'type' => 'aktiva',
            'level' => 2, 'order' => 1, 'is_active' => true, 'is_leaf_account' => true,
        ]);

        $this->receivableAccount = COA::create([
            'code' => '1201', 'name' => 'Piutang Dagang', 'type' => 'aktiva',
            'level' => 2, 'order' => 2, 'is_active' => true, 'is_leaf_account' => true,
        ]);

        $this->revenueAccount = COA::create([
            'code' => '4101', 'name' => 'Pendapatan Penjualan', 'type' => 'pendapatan',
            'level' => 2, 'order' => 1, 'is_active' => true, 'is_leaf_account' => true,
        ]);

        $this->expenseAccount = COA::create([
            'code' => '5101', 'name' => 'Beban Gaji', 'type' => 'beban',
            'level' => 2, 'order' => 1, 'is_active' => true, 'is_leaf_account' => true,
        ]);
    }

    /**
     * Helper: create and post a journal
     */
    private function createPostedJournal(array $entries, ?int $periodId = null, ?string $date = null): JournalMaster
    {
        $journal = $this->journalService->createJournalEntry([
            'journal_date' => $date ?? now()->format('Y-m-d'),
            'id_period' => $periodId ?? $this->currentPeriod->id,
            'status' => 'posted',
            'entries' => $entries,
        ]);

        return $journal;
    }

    // ==========================================
    // Route & Rendering Tests
    // ==========================================

    public function test_general_ledger_page_is_accessible(): void
    {
        $response = $this->get(route('general-ledger'));
        $response->assertStatus(200);
        $response->assertSee('Buku Besar');
    }

    public function test_general_ledger_page_requires_auth(): void
    {
        auth()->logout();
        $response = $this->get(route('general-ledger'));
        $response->assertRedirect(route('login'));
    }

    public function test_component_can_render(): void
    {
        Livewire::test(GeneralLedgerIndex::class)
            ->assertStatus(200);
    }

    // ==========================================
    // Summary View Tests
    // ==========================================

    public function test_summary_shows_empty_state_without_data(): void
    {
        Livewire::test(GeneralLedgerIndex::class)
            ->assertSee('Belum ada data Buku Besar');
    }

    public function test_summary_shows_posted_journal_data(): void
    {
        $this->createPostedJournal([
            ['coa_code' => '1101', 'debit' => 1000000, 'credit' => 0],
            ['coa_code' => '4101', 'debit' => 0, 'credit' => 1000000],
        ]);

        $component = Livewire::test(GeneralLedgerIndex::class);
        $summaryData = $component->viewData('summaryData');

        $this->assertCount(2, $summaryData);

        $cashRow = $summaryData->firstWhere('coa_code', '1101');
        $this->assertEquals(1000000, $cashRow->total_debit);
        $this->assertEquals(0, $cashRow->total_credit);

        $revenueRow = $summaryData->firstWhere('coa_code', '4101');
        $this->assertEquals(0, $revenueRow->total_debit);
        $this->assertEquals(1000000, $revenueRow->total_credit);
    }

    public function test_summary_excludes_draft_journals(): void
    {
        // Create a draft journal (not posted)
        $this->journalService->createJournalEntry([
            'journal_date' => now()->format('Y-m-d'),
            'id_period' => $this->currentPeriod->id,
            'entries' => [
                ['coa_code' => '1101', 'debit' => 500000, 'credit' => 0],
                ['coa_code' => '4101', 'debit' => 0, 'credit' => 500000],
            ],
        ]);

        $component = Livewire::test(GeneralLedgerIndex::class);
        $summaryData = $component->viewData('summaryData');

        $this->assertCount(0, $summaryData);
    }

    public function test_summary_grand_totals_are_correct(): void
    {
        $this->createPostedJournal([
            ['coa_code' => '1101', 'debit' => 1000000, 'credit' => 0],
            ['coa_code' => '4101', 'debit' => 0, 'credit' => 1000000],
        ]);

        $component = Livewire::test(GeneralLedgerIndex::class);

        $this->assertEquals(1000000, $component->viewData('grandTotalDebit'));
        $this->assertEquals(1000000, $component->viewData('grandTotalCredit'));
    }

    public function test_summary_accumulates_multiple_journals(): void
    {
        $this->createPostedJournal([
            ['coa_code' => '1101', 'debit' => 500000, 'credit' => 0],
            ['coa_code' => '4101', 'debit' => 0, 'credit' => 500000],
        ]);

        $this->createPostedJournal([
            ['coa_code' => '1101', 'debit' => 300000, 'credit' => 0],
            ['coa_code' => '4101', 'debit' => 0, 'credit' => 300000],
        ]);

        $component = Livewire::test(GeneralLedgerIndex::class);
        $summaryData = $component->viewData('summaryData');

        $cashRow = $summaryData->firstWhere('coa_code', '1101');
        $this->assertEquals(800000, $cashRow->total_debit);
        $this->assertEquals(2, $cashRow->total_transactions);
    }

    // ==========================================
    // Filter Tests - Period
    // ==========================================

    public function test_filter_by_period_shows_only_matching_data(): void
    {
        // Journal in current period
        $this->createPostedJournal([
            ['coa_code' => '1101', 'debit' => 1000000, 'credit' => 0],
            ['coa_code' => '4101', 'debit' => 0, 'credit' => 1000000],
        ], $this->currentPeriod->id);

        // Journal in previous period
        $prevDate = now()->subMonth()->format('Y-m-') . '15';
        $this->createPostedJournal([
            ['coa_code' => '1101', 'debit' => 500000, 'credit' => 0],
            ['coa_code' => '4101', 'debit' => 0, 'credit' => 500000],
        ], $this->prevPeriod->id, $prevDate);

        // Filter by current period only
        $component = Livewire::test(GeneralLedgerIndex::class)
            ->set('filterPeriod', $this->currentPeriod->id);

        $summaryData = $component->viewData('summaryData');
        $cashRow = $summaryData->firstWhere('coa_code', '1101');

        $this->assertEquals(1000000, $cashRow->total_debit);
    }

    public function test_filter_by_previous_period(): void
    {
        $this->createPostedJournal([
            ['coa_code' => '1101', 'debit' => 1000000, 'credit' => 0],
            ['coa_code' => '4101', 'debit' => 0, 'credit' => 1000000],
        ], $this->currentPeriod->id);

        $prevDate = now()->subMonth()->format('Y-m-') . '15';
        $this->createPostedJournal([
            ['coa_code' => '1101', 'debit' => 200000, 'credit' => 0],
            ['coa_code' => '4101', 'debit' => 0, 'credit' => 200000],
        ], $this->prevPeriod->id, $prevDate);

        $component = Livewire::test(GeneralLedgerIndex::class)
            ->set('filterPeriod', $this->prevPeriod->id);

        $summaryData = $component->viewData('summaryData');
        $cashRow = $summaryData->firstWhere('coa_code', '1101');

        $this->assertEquals(200000, $cashRow->total_debit);
    }

    // ==========================================
    // Filter Tests - COA
    // ==========================================

    public function test_filter_by_specific_coa(): void
    {
        $this->createPostedJournal([
            ['coa_code' => '1101', 'debit' => 1000000, 'credit' => 0],
            ['coa_code' => '4101', 'debit' => 0, 'credit' => 1000000],
        ]);

        $component = Livewire::test(GeneralLedgerIndex::class)
            ->set('filterCoa', $this->cashAccount->id);

        $summaryData = $component->viewData('summaryData');

        $this->assertCount(1, $summaryData);
        $this->assertEquals('1101', $summaryData->first()->coa_code);
    }

    // ==========================================
    // Filter Tests - COA Type
    // ==========================================

    public function test_filter_by_coa_type(): void
    {
        $this->createPostedJournal([
            ['coa_code' => '1101', 'debit' => 1000000, 'credit' => 0],
            ['coa_code' => '4101', 'debit' => 0, 'credit' => 1000000],
        ]);

        $component = Livewire::test(GeneralLedgerIndex::class)
            ->set('filterCoaType', 'aktiva');

        $summaryData = $component->viewData('summaryData');

        $this->assertTrue($summaryData->every(fn ($row) => $row->coa_type === 'aktiva'));
    }

    public function test_filter_coa_type_resets_coa_filter(): void
    {
        Livewire::test(GeneralLedgerIndex::class)
            ->set('filterCoa', $this->cashAccount->id)
            ->assertSet('filterCoa', $this->cashAccount->id)
            ->set('filterCoaType', 'pendapatan')
            ->assertSet('filterCoa', '');
    }

    // ==========================================
    // Filter Tests - Date Range
    // ==========================================

    public function test_filter_by_date_range(): void
    {
        $this->createPostedJournal([
            ['coa_code' => '1101', 'debit' => 1000000, 'credit' => 0],
            ['coa_code' => '4101', 'debit' => 0, 'credit' => 1000000],
        ], $this->currentPeriod->id, now()->format('Y-m-') . '10');

        $this->createPostedJournal([
            ['coa_code' => '1101', 'debit' => 500000, 'credit' => 0],
            ['coa_code' => '4101', 'debit' => 0, 'credit' => 500000],
        ], $this->currentPeriod->id, now()->format('Y-m-') . '01');

        // Filter to only get the 10th
        $component = Livewire::test(GeneralLedgerIndex::class)
            ->set('dateFrom', now()->format('Y-m-') . '10')
            ->set('dateTo', now()->format('Y-m-') . '10');

        $summaryData = $component->viewData('summaryData');
        $cashRow = $summaryData->firstWhere('coa_code', '1101');

        $this->assertEquals(1000000, $cashRow->total_debit);
    }

    // ==========================================
    // Filter Tests - Clear
    // ==========================================

    public function test_clear_filters_resets_all(): void
    {
        Livewire::test(GeneralLedgerIndex::class)
            ->set('filterPeriod', $this->currentPeriod->id)
            ->set('filterCoa', $this->cashAccount->id)
            ->set('filterCoaType', 'aktiva')
            ->set('dateFrom', '2026-01-01')
            ->set('dateTo', '2026-01-31')
            ->call('clearFilters')
            ->assertSet('filterPeriod', '')
            ->assertSet('filterCoa', '')
            ->assertSet('filterCoaType', '')
            ->assertSet('dateFrom', '')
            ->assertSet('dateTo', '')
            ->assertSet('showDetail', false);
    }

    // ==========================================
    // Detail View Tests
    // ==========================================

    public function test_can_view_detail_for_specific_coa(): void
    {
        $this->createPostedJournal([
            ['coa_code' => '1101', 'description' => 'Cash receipt', 'debit' => 1000000, 'credit' => 0],
            ['coa_code' => '4101', 'description' => 'Sales', 'debit' => 0, 'credit' => 1000000],
        ]);

        Livewire::test(GeneralLedgerIndex::class)
            ->call('viewDetail', $this->cashAccount->id)
            ->assertSet('showDetail', true)
            ->assertSet('selectedCoa.id', $this->cashAccount->id);
    }

    public function test_detail_shows_individual_transactions(): void
    {
        $this->createPostedJournal([
            ['coa_code' => '1101', 'description' => 'Cash receipt 1', 'debit' => 1000000, 'credit' => 0],
            ['coa_code' => '4101', 'debit' => 0, 'credit' => 1000000],
        ]);

        $this->createPostedJournal([
            ['coa_code' => '1101', 'description' => 'Cash receipt 2', 'debit' => 500000, 'credit' => 0],
            ['coa_code' => '4101', 'debit' => 0, 'credit' => 500000],
        ]);

        $component = Livewire::test(GeneralLedgerIndex::class)
            ->call('viewDetail', $this->cashAccount->id);

        $detailData = $component->viewData('detailData');

        $this->assertCount(2, $detailData);
        $this->assertEquals(1000000, $detailData[0]->debit);
        $this->assertEquals(500000, $detailData[1]->debit);
    }

    public function test_detail_calculates_running_balance(): void
    {
        $this->createPostedJournal([
            ['coa_code' => '1101', 'description' => 'Debit 1', 'debit' => 1000000, 'credit' => 0],
            ['coa_code' => '4101', 'debit' => 0, 'credit' => 1000000],
        ]);

        $this->createPostedJournal([
            ['coa_code' => '5101', 'description' => 'Expense', 'debit' => 300000, 'credit' => 0],
            ['coa_code' => '1101', 'description' => 'Cash paid', 'debit' => 0, 'credit' => 300000],
        ]);

        $component = Livewire::test(GeneralLedgerIndex::class)
            ->call('viewDetail', $this->cashAccount->id);

        $detailData = $component->viewData('detailData');

        $this->assertCount(2, $detailData);
        // First entry: 1,000,000 debit -> balance = 1,000,000
        $this->assertEquals(1000000, $detailData[0]->running_balance);
        // Second entry: 300,000 credit -> balance = 700,000
        $this->assertEquals(700000, $detailData[1]->running_balance);
    }

    public function test_detail_respects_period_filter(): void
    {
        $this->createPostedJournal([
            ['coa_code' => '1101', 'debit' => 1000000, 'credit' => 0],
            ['coa_code' => '4101', 'debit' => 0, 'credit' => 1000000],
        ], $this->currentPeriod->id);

        $prevDate = now()->subMonth()->format('Y-m-') . '15';
        $this->createPostedJournal([
            ['coa_code' => '1101', 'debit' => 500000, 'credit' => 0],
            ['coa_code' => '4101', 'debit' => 0, 'credit' => 500000],
        ], $this->prevPeriod->id, $prevDate);

        $component = Livewire::test(GeneralLedgerIndex::class)
            ->set('filterPeriod', $this->currentPeriod->id)
            ->call('viewDetail', $this->cashAccount->id);

        $detailData = $component->viewData('detailData');
        $this->assertCount(1, $detailData);
        $this->assertEquals(1000000, $detailData[0]->debit);
    }

    public function test_detail_empty_when_no_transactions(): void
    {
        $component = Livewire::test(GeneralLedgerIndex::class)
            ->call('viewDetail', $this->expenseAccount->id);

        $detailData = $component->viewData('detailData');
        $this->assertCount(0, $detailData);
    }

    // ==========================================
    // Navigation Tests
    // ==========================================

    public function test_back_to_summary_resets_detail(): void
    {
        Livewire::test(GeneralLedgerIndex::class)
            ->call('viewDetail', $this->cashAccount->id)
            ->assertSet('showDetail', true)
            ->call('backToSummary')
            ->assertSet('showDetail', false)
            ->assertSet('selectedCoa', null);
    }

    public function test_changing_filter_resets_detail_view(): void
    {
        Livewire::test(GeneralLedgerIndex::class)
            ->call('viewDetail', $this->cashAccount->id)
            ->assertSet('showDetail', true)
            ->set('filterPeriod', $this->currentPeriod->id)
            ->assertSet('showDetail', false);
    }

    // ==========================================
    // Computed Properties Tests
    // ==========================================

    public function test_periods_property_returns_ordered_periods(): void
    {
        $component = Livewire::test(GeneralLedgerIndex::class);
        $periods = $component->viewData('periods');

        $this->assertCount(2, $periods);
        // Should be ordered desc by year and month
        $this->assertTrue($periods[0]->year >= $periods[1]->year);
    }

    public function test_coas_property_returns_only_active_leaf_accounts(): void
    {
        COA::create([
            'code' => '9999', 'name' => 'Inactive', 'type' => 'beban',
            'level' => 2, 'order' => 1, 'is_active' => false, 'is_leaf_account' => true,
        ]);

        COA::create([
            'code' => '1000', 'name' => 'Parent', 'type' => 'aktiva',
            'level' => 1, 'order' => 1, 'is_active' => true, 'is_leaf_account' => false,
        ]);

        $component = Livewire::test(GeneralLedgerIndex::class);
        $coas = $component->viewData('coas');

        $this->assertCount(4, $coas); // Only the 4 active leaf accounts from setUp
        $this->assertTrue($coas->every(fn ($coa) => $coa->is_active && $coa->is_leaf_account));
    }

    public function test_coas_filtered_by_type(): void
    {
        $component = Livewire::test(GeneralLedgerIndex::class)
            ->set('filterCoaType', 'aktiva');

        $coas = $component->viewData('coas');

        $this->assertTrue($coas->every(fn ($coa) => $coa->type === 'aktiva'));
    }

    // ==========================================
    // Combined Filter Tests
    // ==========================================

    public function test_combined_period_and_type_filter(): void
    {
        $this->createPostedJournal([
            ['coa_code' => '1101', 'debit' => 1000000, 'credit' => 0],
            ['coa_code' => '4101', 'debit' => 0, 'credit' => 1000000],
        ], $this->currentPeriod->id);

        $component = Livewire::test(GeneralLedgerIndex::class)
            ->set('filterPeriod', $this->currentPeriod->id)
            ->set('filterCoaType', 'aktiva');

        $summaryData = $component->viewData('summaryData');

        $this->assertCount(1, $summaryData);
        $this->assertEquals('1101', $summaryData->first()->coa_code);
    }

    public function test_combined_period_and_date_filter(): void
    {
        $this->createPostedJournal([
            ['coa_code' => '1101', 'debit' => 1000000, 'credit' => 0],
            ['coa_code' => '4101', 'debit' => 0, 'credit' => 1000000],
        ], $this->currentPeriod->id, now()->format('Y-m-') . '05');

        $this->createPostedJournal([
            ['coa_code' => '1101', 'debit' => 500000, 'credit' => 0],
            ['coa_code' => '4101', 'debit' => 0, 'credit' => 500000],
        ], $this->currentPeriod->id, now()->format('Y-m-') . '20');

        $component = Livewire::test(GeneralLedgerIndex::class)
            ->set('filterPeriod', $this->currentPeriod->id)
            ->set('dateFrom', now()->format('Y-m-') . '15')
            ->set('dateTo', now()->format('Y-m-') . '28');

        $summaryData = $component->viewData('summaryData');
        $cashRow = $summaryData->firstWhere('coa_code', '1101');

        $this->assertEquals(500000, $cashRow->total_debit);
    }
}
