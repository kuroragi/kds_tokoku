<?php

namespace Tests\Feature;

use App\Livewire\GeneralLedger\GeneralLedgerDetail;
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
     * Helper: create a posted journal
     */
    private function createPostedJournal(array $entries, ?int $periodId = null, ?string $date = null): JournalMaster
    {
        return $this->journalService->createJournalEntry([
            'journal_date' => $date ?? now()->format('Y-m-d'),
            'id_period' => $periodId ?? $this->currentPeriod->id,
            'status' => 'posted',
            'entries' => $entries,
        ]);
    }

    // ==========================================
    // SUMMARY PAGE (GeneralLedgerIndex) Tests
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

    public function test_index_component_can_render(): void
    {
        Livewire::test(GeneralLedgerIndex::class)
            ->assertStatus(200);
    }

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
    }

    public function test_summary_excludes_draft_journals(): void
    {
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

    public function test_filter_by_period(): void
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
            ->set('filterPeriod', $this->currentPeriod->id);

        $cashRow = $component->viewData('summaryData')->firstWhere('coa_code', '1101');
        $this->assertEquals(1000000, $cashRow->total_debit);
    }

    public function test_filter_by_coa(): void
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

        $component = Livewire::test(GeneralLedgerIndex::class)
            ->set('dateFrom', now()->format('Y-m-') . '10')
            ->set('dateTo', now()->format('Y-m-') . '10');

        $cashRow = $component->viewData('summaryData')->firstWhere('coa_code', '1101');
        $this->assertEquals(1000000, $cashRow->total_debit);
    }

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
            ->assertSet('dateTo', '');
    }

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

    public function test_periods_property_returns_ordered_periods(): void
    {
        $component = Livewire::test(GeneralLedgerIndex::class);
        $periods = $component->viewData('periods');

        $this->assertCount(2, $periods);
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

        $this->assertCount(4, $coas);
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
    // DETAIL PAGE (GeneralLedgerDetail) Tests
    // ==========================================

    public function test_detail_page_is_accessible(): void
    {
        $response = $this->get(route('general-ledger.detail', $this->cashAccount));
        $response->assertStatus(200);
        $response->assertSee('Detail Buku Besar');
        $response->assertSee($this->cashAccount->code);
        $response->assertSee($this->cashAccount->name);
    }

    public function test_detail_page_requires_auth(): void
    {
        auth()->logout();
        $response = $this->get(route('general-ledger.detail', $this->cashAccount));
        $response->assertRedirect(route('login'));
    }

    public function test_detail_page_has_back_link_to_summary(): void
    {
        $response = $this->get(route('general-ledger.detail', $this->cashAccount));
        $response->assertSee(route('general-ledger'));
    }

    public function test_detail_component_can_render(): void
    {
        Livewire::test(GeneralLedgerDetail::class, ['coa' => $this->cashAccount])
            ->assertStatus(200)
            ->assertSee($this->cashAccount->code)
            ->assertSee($this->cashAccount->name);
    }

    public function test_detail_shows_empty_state_when_no_transactions(): void
    {
        Livewire::test(GeneralLedgerDetail::class, ['coa' => $this->expenseAccount])
            ->assertSee('Tidak ada transaksi');
    }

    public function test_detail_shows_all_transactions_for_coa(): void
    {
        $this->createPostedJournal([
            ['coa_code' => '1101', 'description' => 'Cash receipt 1', 'debit' => 1000000, 'credit' => 0],
            ['coa_code' => '4101', 'debit' => 0, 'credit' => 1000000],
        ]);

        $this->createPostedJournal([
            ['coa_code' => '1101', 'description' => 'Cash receipt 2', 'debit' => 500000, 'credit' => 0],
            ['coa_code' => '4101', 'debit' => 0, 'credit' => 500000],
        ]);

        $component = Livewire::test(GeneralLedgerDetail::class, ['coa' => $this->cashAccount]);

        $detailData = $component->viewData('detailData');
        $this->assertCount(2, $detailData);
        $this->assertEquals(1000000, $detailData[0]->debit);
        $this->assertEquals(500000, $detailData[1]->debit);
    }

    public function test_detail_calculates_running_balance(): void
    {
        $this->createPostedJournal([
            ['coa_code' => '1101', 'debit' => 1000000, 'credit' => 0],
            ['coa_code' => '4101', 'debit' => 0, 'credit' => 1000000],
        ]);

        $this->createPostedJournal([
            ['coa_code' => '5101', 'debit' => 300000, 'credit' => 0],
            ['coa_code' => '1101', 'debit' => 0, 'credit' => 300000],
        ]);

        $component = Livewire::test(GeneralLedgerDetail::class, ['coa' => $this->cashAccount]);

        $detailData = $component->viewData('detailData');
        $this->assertCount(2, $detailData);
        // First: +1,000,000 → balance 1,000,000
        $this->assertEquals(1000000, $detailData[0]->running_balance);
        // Second: -300,000 → balance 700,000
        $this->assertEquals(700000, $detailData[1]->running_balance);
    }

    public function test_detail_shows_correct_totals(): void
    {
        $this->createPostedJournal([
            ['coa_code' => '1101', 'debit' => 1000000, 'credit' => 0],
            ['coa_code' => '4101', 'debit' => 0, 'credit' => 1000000],
        ]);

        $this->createPostedJournal([
            ['coa_code' => '5101', 'debit' => 300000, 'credit' => 0],
            ['coa_code' => '1101', 'debit' => 0, 'credit' => 300000],
        ]);

        $component = Livewire::test(GeneralLedgerDetail::class, ['coa' => $this->cashAccount]);

        $this->assertEquals(1000000, $component->viewData('totalDebit'));
        $this->assertEquals(300000, $component->viewData('totalCredit'));
        $this->assertEquals(700000, $component->viewData('finalBalance'));
    }

    public function test_detail_excludes_draft_journals(): void
    {
        // Posted
        $this->createPostedJournal([
            ['coa_code' => '1101', 'debit' => 1000000, 'credit' => 0],
            ['coa_code' => '4101', 'debit' => 0, 'credit' => 1000000],
        ]);

        // Draft
        $this->journalService->createJournalEntry([
            'journal_date' => now()->format('Y-m-d'),
            'id_period' => $this->currentPeriod->id,
            'entries' => [
                ['coa_code' => '1101', 'debit' => 500000, 'credit' => 0],
                ['coa_code' => '4101', 'debit' => 0, 'credit' => 500000],
            ],
        ]);

        $component = Livewire::test(GeneralLedgerDetail::class, ['coa' => $this->cashAccount]);
        $detailData = $component->viewData('detailData');
        $this->assertCount(1, $detailData);
        $this->assertEquals(1000000, $detailData[0]->debit);
    }

    public function test_detail_filter_by_period(): void
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

        $component = Livewire::test(GeneralLedgerDetail::class, ['coa' => $this->cashAccount])
            ->set('filterPeriod', $this->currentPeriod->id);

        $detailData = $component->viewData('detailData');
        $this->assertCount(1, $detailData);
        $this->assertEquals(1000000, $detailData[0]->debit);
    }

    public function test_detail_filter_by_date_range(): void
    {
        $this->createPostedJournal([
            ['coa_code' => '1101', 'debit' => 1000000, 'credit' => 0],
            ['coa_code' => '4101', 'debit' => 0, 'credit' => 1000000],
        ], $this->currentPeriod->id, now()->format('Y-m-') . '10');

        $this->createPostedJournal([
            ['coa_code' => '1101', 'debit' => 500000, 'credit' => 0],
            ['coa_code' => '4101', 'debit' => 0, 'credit' => 500000],
        ], $this->currentPeriod->id, now()->format('Y-m-') . '01');

        $component = Livewire::test(GeneralLedgerDetail::class, ['coa' => $this->cashAccount])
            ->set('dateFrom', now()->format('Y-m-') . '05')
            ->set('dateTo', now()->format('Y-m-') . '12');

        $detailData = $component->viewData('detailData');
        $this->assertCount(1, $detailData);
        $this->assertEquals(1000000, $detailData[0]->debit);
    }

    public function test_detail_clear_filters(): void
    {
        Livewire::test(GeneralLedgerDetail::class, ['coa' => $this->cashAccount])
            ->set('filterPeriod', $this->currentPeriod->id)
            ->set('dateFrom', '2026-01-01')
            ->set('dateTo', '2026-01-31')
            ->call('clearFilters')
            ->assertSet('filterPeriod', '')
            ->assertSet('dateFrom', '')
            ->assertSet('dateTo', '');
    }

    public function test_detail_running_balance_with_credit_side_account(): void
    {
        // Revenue account: credits increase, debits decrease
        $this->createPostedJournal([
            ['coa_code' => '1101', 'debit' => 1000000, 'credit' => 0],
            ['coa_code' => '4101', 'debit' => 0, 'credit' => 1000000],
        ]);

        $this->createPostedJournal([
            ['coa_code' => '1101', 'debit' => 500000, 'credit' => 0],
            ['coa_code' => '4101', 'debit' => 0, 'credit' => 500000],
        ]);

        $component = Livewire::test(GeneralLedgerDetail::class, ['coa' => $this->revenueAccount]);

        $detailData = $component->viewData('detailData');
        $this->assertCount(2, $detailData);
        // Revenue: debit - credit = 0 - 1,000,000 = -1,000,000
        $this->assertEquals(-1000000, $detailData[0]->running_balance);
        // Revenue: -1,000,000 + (0 - 500,000) = -1,500,000
        $this->assertEquals(-1500000, $detailData[1]->running_balance);

        $this->assertEquals(-1500000, $component->viewData('finalBalance'));
    }

    public function test_detail_periods_property(): void
    {
        $component = Livewire::test(GeneralLedgerDetail::class, ['coa' => $this->cashAccount]);
        $periods = $component->viewData('periods');

        $this->assertCount(2, $periods);
    }

    public function test_detail_combined_period_and_date_filter(): void
    {
        $this->createPostedJournal([
            ['coa_code' => '1101', 'debit' => 1000000, 'credit' => 0],
            ['coa_code' => '4101', 'debit' => 0, 'credit' => 1000000],
        ], $this->currentPeriod->id, now()->format('Y-m-') . '05');

        $this->createPostedJournal([
            ['coa_code' => '1101', 'debit' => 500000, 'credit' => 0],
            ['coa_code' => '4101', 'debit' => 0, 'credit' => 500000],
        ], $this->currentPeriod->id, now()->format('Y-m-') . '20');

        $component = Livewire::test(GeneralLedgerDetail::class, ['coa' => $this->cashAccount])
            ->set('filterPeriod', $this->currentPeriod->id)
            ->set('dateFrom', now()->format('Y-m-') . '15')
            ->set('dateTo', now()->format('Y-m-') . '28');

        $detailData = $component->viewData('detailData');
        $this->assertCount(1, $detailData);
        $this->assertEquals(500000, $detailData[0]->debit);
    }
}
