<?php

namespace Tests\Feature;

use App\Livewire\AdjustmentJournal\AdjustmentJournalForm;
use App\Livewire\AdjustmentJournal\AdjustmentJournalList;
use App\Models\COA;
use App\Models\Journal;
use App\Models\JournalMaster;
use App\Models\Period;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdjustmentJournalTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Period $currentPeriod;
    protected Period $prevPeriod;
    protected COA $depreciationExpense;
    protected COA $accumulatedDepreciation;
    protected COA $accruedExpense;
    protected COA $accruedLiability;

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

        $this->depreciationExpense = COA::create([
            'code' => '5201',
            'name' => 'Beban Penyusutan',
            'type' => 'beban',
            'level' => 2,
            'order' => 1,
            'is_active' => true,
            'is_leaf_account' => true,
        ]);

        $this->accumulatedDepreciation = COA::create([
            'code' => '1209',
            'name' => 'Akumulasi Penyusutan',
            'type' => 'aktiva',
            'level' => 2,
            'order' => 2,
            'is_active' => true,
            'is_leaf_account' => true,
        ]);

        $this->accruedExpense = COA::create([
            'code' => '5301',
            'name' => 'Beban Akrual',
            'type' => 'beban',
            'level' => 2,
            'order' => 3,
            'is_active' => true,
            'is_leaf_account' => true,
        ]);

        $this->accruedLiability = COA::create([
            'code' => '2101',
            'name' => 'Utang Akrual',
            'type' => 'pasiva',
            'level' => 2,
            'order' => 4,
            'is_active' => true,
            'is_leaf_account' => true,
        ]);
    }

    // ======================================
    // Helper Methods
    // ======================================

    private function createAdjustmentJournal(array $overrides = []): JournalMaster
    {
        return JournalMaster::factory()
            ->adjustment()
            ->create(array_merge([
                'id_period' => $this->currentPeriod->id,
            ], $overrides));
    }

    private function createGeneralJournal(array $overrides = []): JournalMaster
    {
        return JournalMaster::factory()->create(array_merge([
            'id_period' => $this->currentPeriod->id,
        ], $overrides));
    }

    private function createAdjustmentWithDetails(array $masterOverrides = []): JournalMaster
    {
        $master = $this->createAdjustmentJournal($masterOverrides);

        Journal::create([
            'id_journal_master' => $master->id,
            'id_coa' => $this->depreciationExpense->id,
            'description' => 'Penyusutan bulan ini',
            'debit' => 500000,
            'credit' => 0,
            'sequence' => 1,
        ]);

        Journal::create([
            'id_journal_master' => $master->id,
            'id_coa' => $this->accumulatedDepreciation->id,
            'description' => 'Akumulasi penyusutan',
            'debit' => 0,
            'credit' => 500000,
            'sequence' => 2,
        ]);

        $master->update(['total_debit' => 500000, 'total_credit' => 500000]);

        return $master->fresh();
    }

    // ======================================
    // Route & Page Tests
    // ======================================

    public function test_adjustment_journal_page_requires_authentication(): void
    {
        auth()->logout();
        $this->get(route('adjustment-journal'))->assertRedirect(route('login'));
    }

    public function test_adjustment_journal_page_accessible_for_authenticated_user(): void
    {
        $this->get(route('adjustment-journal'))->assertStatus(200);
    }

    public function test_adjustment_journal_page_contains_list_component(): void
    {
        $this->get(route('adjustment-journal'))
            ->assertSeeLivewire('adjustment-journal.adjustment-journal-list');
    }

    public function test_adjustment_journal_page_contains_form_component(): void
    {
        $this->get(route('adjustment-journal'))
            ->assertSeeLivewire('adjustment-journal.adjustment-journal-form');
    }

    public function test_adjustment_journal_page_title(): void
    {
        $this->get(route('adjustment-journal'))
            ->assertSee('Jurnal Penyesuaian');
    }

    // ======================================
    // List Component Rendering
    // ======================================

    public function test_list_component_renders(): void
    {
        Livewire::test(AdjustmentJournalList::class)
            ->assertStatus(200);
    }

    public function test_list_shows_adjustment_journals_only(): void
    {
        $adjustment = $this->createAdjustmentJournal(['description' => 'Penyesuaian penyusutan']);
        $general = $this->createGeneralJournal(['description' => 'Penjualan barang umum']);

        Livewire::test(AdjustmentJournalList::class)
            ->assertSee($adjustment->journal_no)
            ->assertDontSee($general->journal_no);
    }

    public function test_list_does_not_show_general_journals(): void
    {
        $general1 = $this->createGeneralJournal();
        $general2 = $this->createGeneralJournal();

        Livewire::test(AdjustmentJournalList::class)
            ->assertDontSee($general1->journal_no)
            ->assertDontSee($general2->journal_no);
    }

    public function test_list_shows_empty_state_when_no_adjustments(): void
    {
        Livewire::test(AdjustmentJournalList::class)
            ->assertSee('Tidak ada Jurnal Penyesuaian ditemukan');
    }

    // ======================================
    // List Filters
    // ======================================

    public function test_list_search_filters_by_journal_no(): void
    {
        $adj1 = $this->createAdjustmentJournal(['journal_no' => 'AJE/2026/01/0001']);
        $adj2 = $this->createAdjustmentJournal(['journal_no' => 'AJE/2026/01/0002']);

        Livewire::test(AdjustmentJournalList::class)
            ->set('search', '0001')
            ->assertSee($adj1->journal_no)
            ->assertDontSee($adj2->journal_no);
    }

    public function test_list_search_filters_by_description(): void
    {
        $adj1 = $this->createAdjustmentJournal(['description' => 'Penyesuaian penyusutan']);
        $adj2 = $this->createAdjustmentJournal(['description' => 'Akrual gaji']);

        Livewire::test(AdjustmentJournalList::class)
            ->set('search', 'penyusutan')
            ->assertSee($adj1->journal_no)
            ->assertDontSee($adj2->journal_no);
    }

    public function test_list_search_filters_by_reference(): void
    {
        $adj1 = $this->createAdjustmentJournal(['reference' => 'REF-ADJ-001']);
        $adj2 = $this->createAdjustmentJournal(['reference' => 'REF-ADJ-999']);

        Livewire::test(AdjustmentJournalList::class)
            ->set('search', 'REF-ADJ-001')
            ->assertSee($adj1->journal_no)
            ->assertDontSee($adj2->journal_no);
    }

    public function test_list_filter_by_status_draft(): void
    {
        $draft = $this->createAdjustmentJournal(['status' => 'draft']);
        $posted = $this->createAdjustmentJournal(['status' => 'posted']);

        Livewire::test(AdjustmentJournalList::class)
            ->set('filterStatus', 'draft')
            ->assertSee($draft->journal_no)
            ->assertDontSee($posted->journal_no);
    }

    public function test_list_filter_by_status_posted(): void
    {
        $draft = $this->createAdjustmentJournal(['status' => 'draft']);
        $posted = $this->createAdjustmentJournal(['status' => 'posted']);

        Livewire::test(AdjustmentJournalList::class)
            ->set('filterStatus', 'posted')
            ->assertSee($posted->journal_no)
            ->assertDontSee($draft->journal_no);
    }

    public function test_list_filter_by_period(): void
    {
        $current = $this->createAdjustmentJournal(['id_period' => $this->currentPeriod->id]);
        $prev = $this->createAdjustmentJournal(['id_period' => $this->prevPeriod->id]);

        Livewire::test(AdjustmentJournalList::class)
            ->set('filterPeriod', $this->currentPeriod->id)
            ->assertSee($current->journal_no)
            ->assertDontSee($prev->journal_no);
    }

    public function test_list_filter_by_date_range(): void
    {
        $adj1 = $this->createAdjustmentJournal([
            'journal_date' => now()->startOfMonth()->format('Y-m-d'),
        ]);
        $adj2 = $this->createAdjustmentJournal([
            'journal_date' => now()->subMonths(2)->format('Y-m-d'),
        ]);

        Livewire::test(AdjustmentJournalList::class)
            ->set('dateFrom', now()->startOfMonth()->format('Y-m-d'))
            ->set('dateTo', now()->endOfMonth()->format('Y-m-d'))
            ->assertSee($adj1->journal_no)
            ->assertDontSee($adj2->journal_no);
    }

    public function test_list_clear_filters_resets_all(): void
    {
        Livewire::test(AdjustmentJournalList::class)
            ->set('search', 'test')
            ->set('filterStatus', 'draft')
            ->set('filterPeriod', $this->currentPeriod->id)
            ->set('dateFrom', '2026-01-01')
            ->set('dateTo', '2026-12-31')
            ->call('clearFilters')
            ->assertSet('search', '')
            ->assertSet('filterStatus', '')
            ->assertSet('filterPeriod', '')
            ->assertSet('dateFrom', '')
            ->assertSet('dateTo', '');
    }

    // ======================================
    // List Sorting
    // ======================================

    public function test_list_sort_toggles_direction(): void
    {
        Livewire::test(AdjustmentJournalList::class)
            ->assertSet('sortDirection', 'desc')
            ->call('sortBy', 'journal_date')
            ->assertSet('sortDirection', 'asc')
            ->call('sortBy', 'journal_date')
            ->assertSet('sortDirection', 'desc');
    }

    public function test_list_sort_by_different_field(): void
    {
        Livewire::test(AdjustmentJournalList::class)
            ->assertSet('sortField', 'journal_date')
            ->call('sortBy', 'journal_no')
            ->assertSet('sortField', 'journal_no')
            ->assertSet('sortDirection', 'asc');
    }

    // ======================================
    // Form Component - Modal & Init
    // ======================================

    public function test_form_component_renders(): void
    {
        Livewire::test(AdjustmentJournalForm::class)
            ->assertStatus(200);
    }

    public function test_form_modal_starts_hidden(): void
    {
        Livewire::test(AdjustmentJournalForm::class)
            ->assertSet('showModal', false);
    }

    public function test_form_open_modal_shows_form(): void
    {
        Livewire::test(AdjustmentJournalForm::class)
            ->call('openModal')
            ->assertSet('showModal', true);
    }

    public function test_form_close_modal_hides_form(): void
    {
        Livewire::test(AdjustmentJournalForm::class)
            ->call('openModal')
            ->assertSet('showModal', true)
            ->call('closeModal')
            ->assertSet('showModal', false);
    }

    public function test_form_generates_aje_number_on_open(): void
    {
        $prefix = 'AJE/' . now()->format('Y') . '/' . now()->format('m');

        $component = Livewire::test(AdjustmentJournalForm::class)
            ->call('openModal');

        $this->assertStringContainsString($prefix, $component->get('journal_no'));
    }

    public function test_form_open_sets_default_date(): void
    {
        Livewire::test(AdjustmentJournalForm::class)
            ->call('openModal')
            ->assertSet('journal_date', now()->format('Y-m-d'));
    }

    public function test_form_open_initializes_two_rows(): void
    {
        Livewire::test(AdjustmentJournalForm::class)
            ->call('openModal')
            ->assertCount('journalDetails', 2);
    }

    public function test_form_auto_selects_period(): void
    {
        Livewire::test(AdjustmentJournalForm::class)
            ->call('openModal')
            ->assertSet('id_period', $this->currentPeriod->id);
    }

    public function test_form_period_changes_with_date(): void
    {
        $prevMonthDate = now()->subMonth()->format('Y-m-') . '15';

        Livewire::test(AdjustmentJournalForm::class)
            ->call('openModal')
            ->assertSet('id_period', $this->currentPeriod->id)
            ->set('journal_date', $prevMonthDate)
            ->assertSet('id_period', $this->prevPeriod->id);
    }

    // ======================================
    // Form - Journal Row Management
    // ======================================

    public function test_form_add_row(): void
    {
        Livewire::test(AdjustmentJournalForm::class)
            ->call('openModal')
            ->assertCount('journalDetails', 2)
            ->call('addJournalRow')
            ->assertCount('journalDetails', 3);
    }

    public function test_form_remove_row(): void
    {
        Livewire::test(AdjustmentJournalForm::class)
            ->call('openModal')
            ->call('addJournalRow')
            ->assertCount('journalDetails', 3)
            ->call('removeJournalRow', 2)
            ->assertCount('journalDetails', 2);
    }

    public function test_form_cannot_remove_below_two_rows(): void
    {
        Livewire::test(AdjustmentJournalForm::class)
            ->call('openModal')
            ->assertCount('journalDetails', 2)
            ->call('removeJournalRow', 0)
            ->assertCount('journalDetails', 2);
    }

    public function test_form_calculates_totals(): void
    {
        Livewire::test(AdjustmentJournalForm::class)
            ->call('openModal')
            ->set('journalDetails.0.debit', 100000)
            ->set('journalDetails.1.credit', 100000)
            ->assertSet('totalDebit', 100000)
            ->assertSet('totalCredit', 100000);
    }

    // ======================================
    // Form - Save (Create)
    // ======================================

    public function test_form_saves_new_adjustment_journal(): void
    {
        Livewire::test(AdjustmentJournalForm::class)
            ->call('openModal')
            ->set('journal_date', now()->format('Y-m-d'))
            ->set('id_period', $this->currentPeriod->id)
            ->set('reference', 'ADJ-REF-001')
            ->set('description', 'Penyesuaian penyusutan peralatan')
            ->set('journalDetails.0.id_coa', $this->depreciationExpense->id)
            ->set('journalDetails.0.debit', 500000)
            ->set('journalDetails.0.credit', 0)
            ->set('journalDetails.1.id_coa', $this->accumulatedDepreciation->id)
            ->set('journalDetails.1.debit', 0)
            ->set('journalDetails.1.credit', 500000)
            ->call('save')
            ->assertDispatched('refreshAdjustmentList')
            ->assertSet('showModal', false);

        $this->assertDatabaseHas('journal_masters', [
            'type' => 'adjustment',
            'reference' => 'ADJ-REF-001',
            'description' => 'Penyesuaian penyusutan peralatan',
            'status' => 'draft',
        ]);

        $master = JournalMaster::where('type', 'adjustment')->first();
        $this->assertNotNull($master);
        $this->assertStringStartsWith('AJE/', $master->journal_no);
        $this->assertEquals(2, $master->journals()->count());
    }

    public function test_form_save_creates_with_adjustment_type(): void
    {
        Livewire::test(AdjustmentJournalForm::class)
            ->call('openModal')
            ->set('journal_date', now()->format('Y-m-d'))
            ->set('id_period', $this->currentPeriod->id)
            ->set('journalDetails.0.id_coa', $this->depreciationExpense->id)
            ->set('journalDetails.0.debit', 100000)
            ->set('journalDetails.0.credit', 0)
            ->set('journalDetails.1.id_coa', $this->accumulatedDepreciation->id)
            ->set('journalDetails.1.debit', 0)
            ->set('journalDetails.1.credit', 100000)
            ->call('save');

        $journal = JournalMaster::first();
        $this->assertEquals('adjustment', $journal->type);
    }

    public function test_form_save_rejects_unbalanced_journal(): void
    {
        Livewire::test(AdjustmentJournalForm::class)
            ->call('openModal')
            ->set('journal_date', now()->format('Y-m-d'))
            ->set('id_period', $this->currentPeriod->id)
            ->set('journalDetails.0.id_coa', $this->depreciationExpense->id)
            ->set('journalDetails.0.debit', 500000)
            ->set('journalDetails.0.credit', 0)
            ->set('journalDetails.1.id_coa', $this->accumulatedDepreciation->id)
            ->set('journalDetails.1.debit', 0)
            ->set('journalDetails.1.credit', 300000)
            ->call('save')
            ->assertDispatched('showAlert', fn ($name, $data) => $data[0]['type'] === 'error');

        $this->assertDatabaseCount('journal_masters', 0);
    }

    public function test_form_validation_requires_journal_date(): void
    {
        Livewire::test(AdjustmentJournalForm::class)
            ->call('openModal')
            ->set('journal_date', '')
            ->set('id_period', $this->currentPeriod->id)
            ->set('journalDetails.0.id_coa', $this->depreciationExpense->id)
            ->set('journalDetails.0.debit', 100000)
            ->set('journalDetails.1.id_coa', $this->accumulatedDepreciation->id)
            ->set('journalDetails.1.credit', 100000)
            ->call('save')
            ->assertHasErrors(['journal_date']);
    }

    public function test_form_validation_requires_period(): void
    {
        Livewire::test(AdjustmentJournalForm::class)
            ->call('openModal')
            ->set('journal_date', now()->format('Y-m-d'))
            ->set('id_period', null)
            ->set('journalDetails.0.id_coa', $this->depreciationExpense->id)
            ->set('journalDetails.0.debit', 100000)
            ->set('journalDetails.1.id_coa', $this->accumulatedDepreciation->id)
            ->set('journalDetails.1.credit', 100000)
            ->call('save')
            ->assertHasErrors(['id_period']);
    }

    public function test_form_validation_requires_coa(): void
    {
        Livewire::test(AdjustmentJournalForm::class)
            ->call('openModal')
            ->set('journal_date', now()->format('Y-m-d'))
            ->set('id_period', $this->currentPeriod->id)
            ->set('journalDetails.0.id_coa', '')
            ->set('journalDetails.0.debit', 100000)
            ->set('journalDetails.1.id_coa', $this->accumulatedDepreciation->id)
            ->set('journalDetails.1.credit', 100000)
            ->call('save')
            ->assertHasErrors(['journalDetails.0.id_coa']);
    }

    // ======================================
    // Form - Edit
    // ======================================

    public function test_form_edit_loads_adjustment_journal(): void
    {
        $master = $this->createAdjustmentWithDetails();

        Livewire::test(AdjustmentJournalForm::class)
            ->call('edit', $master->id)
            ->assertSet('showModal', true)
            ->assertSet('isEditing', true)
            ->assertSet('journalId', $master->id)
            ->assertSet('journal_no', $master->journal_no)
            ->assertSet('reference', $master->reference)
            ->assertSet('description', $master->description);
    }

    public function test_form_edit_loads_journal_details(): void
    {
        $master = $this->createAdjustmentWithDetails();

        Livewire::test(AdjustmentJournalForm::class)
            ->call('edit', $master->id)
            ->assertCount('journalDetails', 2);
    }

    public function test_form_edit_cannot_load_general_journal(): void
    {
        $general = $this->createGeneralJournal();

        Livewire::test(AdjustmentJournalForm::class)
            ->call('edit', $general->id)
            ->assertSet('showModal', false)
            ->assertDispatched('showAlert', fn ($name, $data) => $data[0]['type'] === 'error');
    }

    public function test_form_edit_saves_updated_data(): void
    {
        $master = $this->createAdjustmentWithDetails();

        Livewire::test(AdjustmentJournalForm::class)
            ->call('edit', $master->id)
            ->set('description', 'Penyesuaian diperbarui')
            ->set('reference', 'REF-UPDATED')
            ->call('save')
            ->assertDispatched('refreshAdjustmentList');

        $this->assertDatabaseHas('journal_masters', [
            'id' => $master->id,
            'description' => 'Penyesuaian diperbarui',
            'reference' => 'REF-UPDATED',
            'type' => 'adjustment',
        ]);
    }

    // ======================================
    // List - Delete
    // ======================================

    public function test_list_delete_draft_adjustment(): void
    {
        $master = $this->createAdjustmentWithDetails();

        Livewire::test(AdjustmentJournalList::class)
            ->call('deleteAdjustment', $master->id)
            ->assertDispatched('showAlert', fn ($name, $data) => $data[0]['type'] === 'success')
            ->assertDispatched('adjustmentDeleted');

        $this->assertSoftDeleted('journal_masters', ['id' => $master->id]);
    }

    public function test_list_cannot_delete_posted_adjustment(): void
    {
        $master = $this->createAdjustmentWithDetails(['status' => 'posted']);

        Livewire::test(AdjustmentJournalList::class)
            ->call('deleteAdjustment', $master->id)
            ->assertDispatched('showAlert', fn ($name, $data) => $data[0]['type'] === 'error');

        $this->assertDatabaseHas('journal_masters', ['id' => $master->id]);
    }

    public function test_list_delete_removes_journal_details(): void
    {
        $master = $this->createAdjustmentWithDetails();
        $detailCount = $master->journals()->count();
        $this->assertEquals(2, $detailCount);

        Livewire::test(AdjustmentJournalList::class)
            ->call('deleteAdjustment', $master->id);

        // Journal details are soft-deleted
        $this->assertEquals(0, \App\Models\Journal::where('id_journal_master', $master->id)->count());
    }

    // ======================================
    // List - Post
    // ======================================

    public function test_list_post_draft_adjustment(): void
    {
        $master = $this->createAdjustmentWithDetails(['status' => 'draft']);

        Livewire::test(AdjustmentJournalList::class)
            ->call('postAdjustment', $master->id)
            ->assertDispatched('showAlert', fn ($name, $data) => $data[0]['type'] === 'success');

        $this->assertDatabaseHas('journal_masters', [
            'id' => $master->id,
            'status' => 'posted',
        ]);
    }

    public function test_list_cannot_post_already_posted_adjustment(): void
    {
        $master = $this->createAdjustmentWithDetails(['status' => 'posted']);

        Livewire::test(AdjustmentJournalList::class)
            ->call('postAdjustment', $master->id)
            ->assertDispatched('showAlert', fn ($name, $data) => $data[0]['type'] === 'warning');
    }

    public function test_list_cannot_post_unbalanced_adjustment(): void
    {
        $master = $this->createAdjustmentJournal(['status' => 'draft']);

        Journal::create([
            'id_journal_master' => $master->id,
            'id_coa' => $this->depreciationExpense->id,
            'debit' => 500000,
            'credit' => 0,
            'sequence' => 1,
        ]);

        Journal::create([
            'id_journal_master' => $master->id,
            'id_coa' => $this->accumulatedDepreciation->id,
            'debit' => 0,
            'credit' => 300000,
            'sequence' => 2,
        ]);

        $master->update(['total_debit' => 500000, 'total_credit' => 300000]);

        Livewire::test(AdjustmentJournalList::class)
            ->call('postAdjustment', $master->id)
            ->assertDispatched('showAlert', fn ($name, $data) => $data[0]['type'] === 'error');

        $this->assertDatabaseHas('journal_masters', [
            'id' => $master->id,
            'status' => 'draft',
        ]);
    }

    // ======================================
    // Type Isolation Tests
    // ======================================

    public function test_general_journal_list_excludes_adjustment_type(): void
    {
        $adjustment = $this->createAdjustmentJournal(['description' => 'Adjustment specific']);
        $general = $this->createGeneralJournal(['description' => 'General specific']);

        Livewire::test(\App\Livewire\Journal\JournalList::class)
            ->assertSee($general->journal_no)
            ->assertDontSee($adjustment->journal_no);
    }

    public function test_adjustment_journal_list_excludes_general_type(): void
    {
        $adjustment = $this->createAdjustmentJournal(['description' => 'Adjustment only']);
        $general = $this->createGeneralJournal(['description' => 'General only']);

        Livewire::test(AdjustmentJournalList::class)
            ->assertSee($adjustment->journal_no)
            ->assertDontSee($general->journal_no);
    }

    // ======================================
    // Journal Number Generation
    // ======================================

    public function test_form_generates_aje_prefix(): void
    {
        $component = Livewire::test(AdjustmentJournalForm::class)
            ->call('openModal');

        $this->assertStringContainsString('AJE/', $component->get('journal_no'));
    }

    public function test_form_aje_number_increments(): void
    {
        $now = now();
        $this->createAdjustmentJournal([
            'journal_no' => 'AJE/' . $now->format('Y') . '/' . $now->format('m') . '/0001',
        ]);

        $component = Livewire::test(AdjustmentJournalForm::class)
            ->call('openModal');

        $journalNo = $component->get('journal_no');
        $expected = 'AJE/' . $now->format('Y') . '/' . $now->format('m') . '/0002';
        $this->assertEquals($expected, $journalNo);
    }

    public function test_form_aje_number_starts_at_0001(): void
    {
        $component = Livewire::test(AdjustmentJournalForm::class)
            ->call('openModal');

        $journalNo = $component->get('journal_no');
        $expected = 'AJE/' . now()->format('Y') . '/' . now()->format('m') . '/0001';
        $this->assertEquals($expected, $journalNo);
    }

    // ======================================
    // Model Scopes
    // ======================================

    public function test_model_general_scope(): void
    {
        $this->createGeneralJournal();
        $this->createGeneralJournal();
        $this->createAdjustmentJournal();

        $this->assertEquals(2, JournalMaster::general()->count());
    }

    public function test_model_adjustment_scope(): void
    {
        $this->createGeneralJournal();
        $this->createAdjustmentJournal();
        $this->createAdjustmentJournal();

        $this->assertEquals(2, JournalMaster::adjustment()->count());
    }

    public function test_model_oftype_scope(): void
    {
        $this->createGeneralJournal();
        $this->createAdjustmentJournal();

        $this->assertEquals(1, JournalMaster::ofType('general')->count());
        $this->assertEquals(1, JournalMaster::ofType('adjustment')->count());
    }

    // ======================================
    // JournalService Type-Aware Number Generation
    // ======================================

    public function test_service_generates_jrn_prefix_for_general(): void
    {
        $service = app(\App\Services\JournalService::class);
        $number = $service->generateJournalNumber(now()->format('Y-m-d'), 'general');
        $this->assertStringStartsWith('JRN/', $number);
    }

    public function test_service_generates_aje_prefix_for_adjustment(): void
    {
        $service = app(\App\Services\JournalService::class);
        $number = $service->generateJournalNumber(now()->format('Y-m-d'), 'adjustment');
        $this->assertStringStartsWith('AJE/', $number);
    }

    // ======================================
    // Form Reset
    // ======================================

    public function test_form_close_resets_all_fields(): void
    {
        $master = $this->createAdjustmentWithDetails();

        Livewire::test(AdjustmentJournalForm::class)
            ->call('edit', $master->id)
            ->assertSet('isEditing', true)
            ->call('closeModal')
            ->assertSet('showModal', false)
            ->assertSet('isEditing', false)
            ->assertSet('journalId', null)
            ->assertSet('journal_no', '')
            ->assertSet('reference', '')
            ->assertSet('description', '')
            ->assertSet('totalDebit', 0)
            ->assertSet('totalCredit', 0)
            ->assertCount('journalDetails', 2);
    }
}
