<?php

namespace Tests\Feature;

use App\Livewire\Journal\JournalForm;
use App\Models\COA;
use App\Models\Journal;
use App\Models\JournalMaster;
use App\Models\Period;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class JournalFormTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Period $currentPeriod;
    protected Period $prevPeriod;
    protected COA $cashAccount;
    protected COA $revenueAccount;
    protected COA $expenseAccount;

    protected function setUp(): void
    {
        parent::setUp();

        // Create user without triggering Blameable events, then authenticate
        $this->user = User::withoutEvents(function () {
            return User::factory()->create();
        });
        $this->actingAs($this->user);

        // Create periods
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

        // Create COA accounts
        $this->cashAccount = COA::create([
            'code' => '1101',
            'name' => 'Kas di Tangan',
            'type' => 'aktiva',
            'level' => 2,
            'order' => 1,
            'is_active' => true,
            'is_leaf_account' => true,
        ]);

        $this->revenueAccount = COA::create([
            'code' => '4101',
            'name' => 'Pendapatan Penjualan',
            'type' => 'pendapatan',
            'level' => 2,
            'order' => 1,
            'is_active' => true,
            'is_leaf_account' => true,
        ]);

        $this->expenseAccount = COA::create([
            'code' => '5101',
            'name' => 'Beban Gaji',
            'type' => 'beban',
            'level' => 2,
            'order' => 1,
            'is_active' => true,
            'is_leaf_account' => true,
        ]);
    }

    // ==========================================
    // Modal & Initialization Tests
    // ==========================================

    public function test_component_can_render(): void
    {
        Livewire::test(JournalForm::class)
            ->assertStatus(200);
    }

    public function test_modal_starts_hidden(): void
    {
        Livewire::test(JournalForm::class)
            ->assertSet('showModal', false);
    }

    public function test_open_modal_shows_form(): void
    {
        Livewire::test(JournalForm::class)
            ->call('openModal')
            ->assertSet('showModal', true);
    }

    public function test_close_modal_hides_form(): void
    {
        Livewire::test(JournalForm::class)
            ->call('openModal')
            ->assertSet('showModal', true)
            ->call('closeModal')
            ->assertSet('showModal', false);
    }

    public function test_open_modal_generates_journal_number(): void
    {
        Livewire::test(JournalForm::class)
            ->call('openModal')
            ->assertNotSet('journal_no', '');
    }

    public function test_open_modal_sets_default_date_to_today(): void
    {
        Livewire::test(JournalForm::class)
            ->call('openModal')
            ->assertSet('journal_date', now()->format('Y-m-d'));
    }

    public function test_open_modal_initializes_two_journal_rows(): void
    {
        Livewire::test(JournalForm::class)
            ->call('openModal')
            ->assertCount('journalDetails', 2);
    }

    // ==========================================
    // Period Auto-Selection Tests
    // ==========================================

    public function test_period_auto_selects_on_modal_open(): void
    {
        Livewire::test(JournalForm::class)
            ->call('openModal')
            ->assertSet('id_period', $this->currentPeriod->id);
    }

    public function test_period_auto_selects_when_date_changes(): void
    {
        $prevMonthDate = now()->subMonth()->format('Y-m-') . '15';

        Livewire::test(JournalForm::class)
            ->call('openModal')
            ->assertSet('id_period', $this->currentPeriod->id)
            ->set('journal_date', $prevMonthDate)
            ->assertSet('id_period', $this->prevPeriod->id);
    }

    public function test_period_clears_when_no_matching_period(): void
    {
        // Set a date far in the future with no period
        Livewire::test(JournalForm::class)
            ->call('openModal')
            ->set('journal_date', '2099-12-31')
            ->assertSet('id_period', null);
    }

    public function test_period_shows_warning_when_no_match(): void
    {
        Livewire::test(JournalForm::class)
            ->call('openModal')
            ->set('journal_date', '2099-12-31')
            ->assertDispatched('showAlert', fn ($name, $data) => $data[0]['type'] === 'warning');
    }

    public function test_period_shows_info_when_match_found(): void
    {
        $prevMonthDate = now()->subMonth()->format('Y-m-') . '15';

        Livewire::test(JournalForm::class)
            ->call('openModal')
            ->set('journal_date', $prevMonthDate)
            ->assertDispatched('showAlert', fn ($name, $data) => $data[0]['type'] === 'info');
    }

    // ==========================================
    // Journal Row Management Tests
    // ==========================================

    public function test_can_add_journal_row(): void
    {
        Livewire::test(JournalForm::class)
            ->call('openModal')
            ->assertCount('journalDetails', 2)
            ->call('addJournalRow')
            ->assertCount('journalDetails', 3);
    }

    public function test_can_remove_journal_row_when_more_than_two(): void
    {
        Livewire::test(JournalForm::class)
            ->call('openModal')
            ->call('addJournalRow')
            ->assertCount('journalDetails', 3)
            ->call('removeJournalRow', 2)
            ->assertCount('journalDetails', 2);
    }

    public function test_cannot_remove_row_when_only_two_rows(): void
    {
        Livewire::test(JournalForm::class)
            ->call('openModal')
            ->assertCount('journalDetails', 2)
            ->call('removeJournalRow', 0)
            ->assertCount('journalDetails', 2);
    }

    public function test_new_row_has_default_values(): void
    {
        $component = Livewire::test(JournalForm::class)
            ->call('openModal');

        $details = $component->get('journalDetails');
        $firstRow = $details[0];

        $this->assertNull($firstRow['id']);
        $this->assertEquals('', $firstRow['id_coa']);
        $this->assertEquals('', $firstRow['description']);
        $this->assertEquals(0, $firstRow['debit']);
        $this->assertEquals(0, $firstRow['credit']);
    }

    // ==========================================
    // Totals Calculation Tests
    // ==========================================

    public function test_totals_calculated_correctly(): void
    {
        Livewire::test(JournalForm::class)
            ->call('openModal')
            ->set('journalDetails.0.debit', 500000)
            ->set('journalDetails.1.credit', 500000)
            ->assertSet('totalDebit', 500000)
            ->assertSet('totalCredit', 500000);
    }

    public function test_totals_update_when_row_removed(): void
    {
        Livewire::test(JournalForm::class)
            ->call('openModal')
            ->call('addJournalRow')
            ->set('journalDetails.0.debit', 300000)
            ->set('journalDetails.1.debit', 200000)
            ->set('journalDetails.2.credit', 500000)
            ->assertSet('totalDebit', 500000)
            ->assertSet('totalCredit', 500000)
            ->call('removeJournalRow', 1)
            ->assertSet('totalDebit', 300000)
            ->assertSet('totalCredit', 500000);
    }

    // ==========================================
    // Validation Tests
    // ==========================================

    public function test_validation_requires_journal_date(): void
    {
        Livewire::test(JournalForm::class)
            ->call('openModal')
            ->set('journal_date', '')
            ->set('journalDetails.0.id_coa', $this->cashAccount->id)
            ->set('journalDetails.0.debit', 100000)
            ->set('journalDetails.1.id_coa', $this->revenueAccount->id)
            ->set('journalDetails.1.credit', 100000)
            ->call('save')
            ->assertHasErrors(['journal_date']);
    }

    public function test_validation_requires_period(): void
    {
        Livewire::test(JournalForm::class)
            ->call('openModal')
            ->set('id_period', null)
            ->set('journalDetails.0.id_coa', $this->cashAccount->id)
            ->set('journalDetails.0.debit', 100000)
            ->set('journalDetails.1.id_coa', $this->revenueAccount->id)
            ->set('journalDetails.1.credit', 100000)
            ->call('save')
            ->assertHasErrors(['id_period']);
    }

    public function test_validation_requires_coa_for_each_detail(): void
    {
        Livewire::test(JournalForm::class)
            ->call('openModal')
            ->set('journalDetails.0.id_coa', '')
            ->set('journalDetails.0.debit', 100000)
            ->set('journalDetails.1.id_coa', $this->revenueAccount->id)
            ->set('journalDetails.1.credit', 100000)
            ->call('save')
            ->assertHasErrors(['journalDetails.0.id_coa']);
    }

    public function test_validation_rejects_negative_amounts(): void
    {
        Livewire::test(JournalForm::class)
            ->call('openModal')
            ->set('journalDetails.0.id_coa', $this->cashAccount->id)
            ->set('journalDetails.0.debit', -100)
            ->set('journalDetails.1.id_coa', $this->revenueAccount->id)
            ->set('journalDetails.1.credit', 100000)
            ->call('save')
            ->assertHasErrors(['journalDetails.0.debit']);
    }

    // ==========================================
    // Save (Create) Tests
    // ==========================================

    public function test_can_save_balanced_journal(): void
    {
        Livewire::test(JournalForm::class)
            ->call('openModal')
            ->set('journal_date', now()->format('Y-m-d'))
            ->set('journalDetails.0.id_coa', $this->cashAccount->id)
            ->set('journalDetails.0.debit', 1000000)
            ->set('journalDetails.0.description', 'Cash received')
            ->set('journalDetails.1.id_coa', $this->revenueAccount->id)
            ->set('journalDetails.1.credit', 1000000)
            ->set('journalDetails.1.description', 'Sales revenue')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('showAlert')
            ->assertDispatched('refreshJournalList')
            ->assertSet('showModal', false);

        $this->assertDatabaseCount('journal_masters', 1);
        $this->assertDatabaseCount('journals', 2);

        $journalMaster = JournalMaster::first();
        $this->assertEquals(1000000, $journalMaster->total_debit);
        $this->assertEquals(1000000, $journalMaster->total_credit);
        $this->assertEquals('draft', $journalMaster->status);
        $this->assertEquals($this->currentPeriod->id, $journalMaster->id_period);
    }

    public function test_cannot_save_unbalanced_journal(): void
    {
        Livewire::test(JournalForm::class)
            ->call('openModal')
            ->set('journal_date', now()->format('Y-m-d'))
            ->set('journalDetails.0.id_coa', $this->cashAccount->id)
            ->set('journalDetails.0.debit', 1000000)
            ->set('journalDetails.1.id_coa', $this->revenueAccount->id)
            ->set('journalDetails.1.credit', 500000)
            ->call('save')
            ->assertDispatched('showAlert', fn ($name, $data) => $data[0]['type'] === 'error');

        $this->assertDatabaseCount('journal_masters', 0);
    }

    public function test_journal_number_auto_increments(): void
    {
        // Create first journal
        Livewire::test(JournalForm::class)
            ->call('openModal')
            ->set('journalDetails.0.id_coa', $this->cashAccount->id)
            ->set('journalDetails.0.debit', 100000)
            ->set('journalDetails.1.id_coa', $this->revenueAccount->id)
            ->set('journalDetails.1.credit', 100000)
            ->call('save');

        // Create second journal
        $component = Livewire::test(JournalForm::class)
            ->call('openModal');

        $journalNo = $component->get('journal_no');
        $expectedSuffix = '0002';
        $this->assertStringEndsWith($expectedSuffix, $journalNo);
    }

    public function test_save_creates_correct_journal_details_sequence(): void
    {
        Livewire::test(JournalForm::class)
            ->call('openModal')
            ->call('addJournalRow')
            ->set('journalDetails.0.id_coa', $this->cashAccount->id)
            ->set('journalDetails.0.debit', 500000)
            ->set('journalDetails.1.id_coa', $this->expenseAccount->id)
            ->set('journalDetails.1.debit', 500000)
            ->set('journalDetails.2.id_coa', $this->revenueAccount->id)
            ->set('journalDetails.2.credit', 1000000)
            ->call('save');

        $journals = Journal::orderBy('sequence')->get();
        $this->assertCount(3, $journals);
        $this->assertEquals(1, $journals[0]->sequence);
        $this->assertEquals(2, $journals[1]->sequence);
        $this->assertEquals(3, $journals[2]->sequence);
    }

    // ==========================================
    // Edit Tests
    // ==========================================

    public function test_can_load_journal_for_editing(): void
    {
        // Create a journal first
        $journalMaster = JournalMaster::create([
            'journal_no' => 'JRN/2026/02/0001',
            'journal_date' => now()->format('Y-m-d'),
            'reference' => 'REF-TEST',
            'description' => 'Test journal',
            'id_period' => $this->currentPeriod->id,
            'total_debit' => 1000000,
            'total_credit' => 1000000,
            'status' => 'draft',
        ]);

        Journal::create([
            'id_journal_master' => $journalMaster->id,
            'id_coa' => $this->cashAccount->id,
            'description' => 'Cash',
            'debit' => 1000000,
            'credit' => 0,
            'sequence' => 1,
        ]);

        Journal::create([
            'id_journal_master' => $journalMaster->id,
            'id_coa' => $this->revenueAccount->id,
            'description' => 'Revenue',
            'debit' => 0,
            'credit' => 1000000,
            'sequence' => 2,
        ]);

        Livewire::test(JournalForm::class)
            ->call('edit', $journalMaster->id)
            ->assertSet('isEditing', true)
            ->assertSet('showModal', true)
            ->assertSet('journal_no', 'JRN/2026/02/0001')
            ->assertSet('reference', 'REF-TEST')
            ->assertSet('description', 'Test journal')
            ->assertSet('id_period', $this->currentPeriod->id)
            ->assertCount('journalDetails', 2)
            ->assertSet('totalDebit', 1000000)
            ->assertSet('totalCredit', 1000000);
    }

    public function test_can_update_existing_journal(): void
    {
        $journalMaster = JournalMaster::create([
            'journal_no' => 'JRN/2026/02/0001',
            'journal_date' => now()->format('Y-m-d'),
            'reference' => 'REF-OLD',
            'description' => 'Old description',
            'id_period' => $this->currentPeriod->id,
            'total_debit' => 1000000,
            'total_credit' => 1000000,
            'status' => 'draft',
        ]);

        Journal::create([
            'id_journal_master' => $journalMaster->id,
            'id_coa' => $this->cashAccount->id,
            'debit' => 1000000,
            'credit' => 0,
            'sequence' => 1,
        ]);

        Journal::create([
            'id_journal_master' => $journalMaster->id,
            'id_coa' => $this->revenueAccount->id,
            'debit' => 0,
            'credit' => 1000000,
            'sequence' => 2,
        ]);

        Livewire::test(JournalForm::class)
            ->call('edit', $journalMaster->id)
            ->set('reference', 'REF-NEW')
            ->set('description', 'Updated description')
            ->set('journalDetails.0.debit', 2000000)
            ->set('journalDetails.1.credit', 2000000)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showModal', false);

        $journalMaster->refresh();
        $this->assertEquals('REF-NEW', $journalMaster->reference);
        $this->assertEquals('Updated description', $journalMaster->description);
        $this->assertEquals(2000000, $journalMaster->total_debit);
        $this->assertEquals(2000000, $journalMaster->total_credit);
    }

    public function test_edit_nonexistent_journal_shows_error(): void
    {
        Livewire::test(JournalForm::class)
            ->call('edit', 99999)
            ->assertDispatched('showAlert', fn ($name, $data) => $data[0]['type'] === 'error');
    }

    // ==========================================
    // Reset & State Tests
    // ==========================================

    public function test_close_modal_resets_form_state(): void
    {
        Livewire::test(JournalForm::class)
            ->call('openModal')
            ->set('reference', 'TEST-REF')
            ->set('description', 'Some description')
            ->call('closeModal')
            ->assertSet('reference', '')
            ->assertSet('description', '')
            ->assertSet('isEditing', false)
            ->assertSet('journalId', null)
            ->assertSet('totalDebit', 0)
            ->assertSet('totalCredit', 0);
    }

    public function test_open_new_modal_after_edit_resets_state(): void
    {
        $journalMaster = JournalMaster::create([
            'journal_no' => 'JRN/2026/02/0001',
            'journal_date' => now()->format('Y-m-d'),
            'id_period' => $this->currentPeriod->id,
            'total_debit' => 1000000,
            'total_credit' => 1000000,
            'status' => 'draft',
        ]);

        Journal::create([
            'id_journal_master' => $journalMaster->id,
            'id_coa' => $this->cashAccount->id,
            'debit' => 1000000,
            'credit' => 0,
            'sequence' => 1,
        ]);

        Journal::create([
            'id_journal_master' => $journalMaster->id,
            'id_coa' => $this->revenueAccount->id,
            'debit' => 0,
            'credit' => 1000000,
            'sequence' => 2,
        ]);

        // Edit journal then close
        Livewire::test(JournalForm::class)
            ->call('edit', $journalMaster->id)
            ->assertSet('isEditing', true)
            ->call('closeModal')
            ->call('openModal')
            ->assertSet('isEditing', false)
            ->assertSet('journalId', null)
            ->assertNotSet('journal_no', 'JRN/2026/02/0001');
    }

    // ==========================================
    // Computed Properties Tests
    // ==========================================

    public function test_coas_property_returns_only_active_leaf_accounts(): void
    {
        // Create an inactive account
        COA::create([
            'code' => '9999',
            'name' => 'Inactive Account',
            'type' => 'beban',
            'level' => 2,
            'order' => 1,
            'is_active' => false,
            'is_leaf_account' => true,
        ]);

        // Create a parent (non-leaf) account
        COA::create([
            'code' => '1000',
            'name' => 'Parent Account',
            'type' => 'aktiva',
            'level' => 1,
            'order' => 1,
            'is_active' => true,
            'is_leaf_account' => false,
        ]);

        $component = Livewire::test(JournalForm::class);
        $coas = $component->viewData('coas');

        // Should only include active leaf accounts (3 from setUp)
        $this->assertCount(3, $coas);
        $this->assertTrue($coas->every(fn ($coa) => $coa->is_active && $coa->is_leaf_account));
    }

    public function test_periods_property_returns_only_open_periods(): void
    {
        // Close the previous period
        $this->prevPeriod->update(['is_closed' => true, 'closed_at' => now()]);

        $component = Livewire::test(JournalForm::class);
        $periods = $component->viewData('periods');

        // Only the current (open) period should be returned
        $this->assertCount(1, $periods);
        $this->assertEquals($this->currentPeriod->id, $periods->first()->id);
    }

    public function test_periods_property_excludes_closed_periods(): void
    {
        // Close both periods
        $this->currentPeriod->update(['is_closed' => true, 'closed_at' => now()]);
        $this->prevPeriod->update(['is_closed' => true, 'closed_at' => now()]);

        $component = Livewire::test(JournalForm::class);
        $periods = $component->viewData('periods');

        $this->assertCount(0, $periods);
    }

    // ==========================================
    // Closed Period Enforcement Tests
    // ==========================================

    public function test_auto_match_warns_when_period_closed(): void
    {
        $this->currentPeriod->update(['is_closed' => true, 'closed_at' => now()]);

        Livewire::test(JournalForm::class)
            ->call('openModal')
            ->assertSet('id_period', null)
            ->assertDispatched('showAlert', fn ($name, $params) => $params[0]['type'] === 'warning'
                && str_contains($params[0]['message'], 'sudah ditutup'));
    }

    public function test_auto_match_skips_closed_period_selects_open_one(): void
    {
        // Close current month, prev month still open
        $this->currentPeriod->update(['is_closed' => true, 'closed_at' => now()]);

        $prevMonthDate = now()->subMonth()->format('Y-m-') . '15';

        Livewire::test(JournalForm::class)
            ->call('openModal')
            // Auto-match for current date should warn (closed)
            ->assertSet('id_period', null)
            // Switch to prev month date — should auto-select the open prev period
            ->set('journal_date', $prevMonthDate)
            ->assertSet('id_period', $this->prevPeriod->id);
    }

    public function test_cannot_save_journal_to_closed_period(): void
    {
        $this->currentPeriod->update(['is_closed' => true, 'closed_at' => now()]);

        // Use JournalService directly to verify enforcement at service level
        $service = new \App\Services\JournalService();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('sudah ditutup');

        $service->createJournalEntry([
            'journal_date' => now()->format('Y-m-d'),
            'id_period' => $this->currentPeriod->id,
            'entries' => [
                ['coa_code' => $this->cashAccount->code, 'debit' => 100000, 'credit' => 0],
                ['coa_code' => $this->revenueAccount->code, 'debit' => 0, 'credit' => 100000],
            ],
        ]);
    }

    public function test_save_blocks_closed_period_in_livewire(): void
    {
        $this->currentPeriod->update(['is_closed' => true, 'closed_at' => now()]);

        $component = Livewire::test(JournalForm::class)
            ->call('openModal')
            ->set('id_period', $this->currentPeriod->id)
            ->set('journal_date', now()->format('Y-m-d'))
            ->set('journalDetails.0.id_coa', $this->cashAccount->id)
            ->set('journalDetails.0.description', 'Cash')
            ->set('journalDetails.0.debit', 100000)
            ->set('journalDetails.0.credit', 0)
            ->set('journalDetails.1.id_coa', $this->revenueAccount->id)
            ->set('journalDetails.1.description', 'Revenue')
            ->set('journalDetails.1.debit', 0)
            ->set('journalDetails.1.credit', 100000)
            ->call('save');

        // Journal should NOT be saved
        $this->assertDatabaseCount('journal_masters', 0);
    }
}
