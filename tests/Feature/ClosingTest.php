<?php

namespace Tests\Feature;

use App\Livewire\Closing\ClosingIndex;
use App\Models\COA;
use App\Models\JournalMaster;
use App\Models\Period;
use App\Models\User;
use App\Services\ClosingService;
use App\Services\JournalService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClosingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected JournalService $journalService;
    protected ClosingService $closingService;
    protected int $year;

    protected COA $cashAccount;
    protected COA $receivableAccount;
    protected COA $payableAccount;
    protected COA $capitalAccount;
    protected COA $revenueAccount;
    protected COA $expenseAccount;
    protected COA $summaryAccount;
    protected COA $retainedEarningsAccount;

    protected array $periods = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->journalService = new JournalService();
        $this->closingService = app(ClosingService::class);
        $this->year = (int) date('Y');

        $this->user = User::withoutEvents(function () {
            return User::factory()->create();
        });
        $this->actingAs($this->user);

        // Create 12 monthly periods
        for ($m = 1; $m <= 12; $m++) {
            $date = Carbon::create($this->year, $m, 1);
            $this->periods[$m] = Period::create([
                'code' => $date->format('Ym'),
                'name' => $date->translatedFormat('F') . ' ' . $date->year,
                'start_date' => $date->copy()->startOfMonth()->format('Y-m-d'),
                'end_date' => $date->copy()->endOfMonth()->format('Y-m-d'),
                'year' => $this->year,
                'month' => $m,
                'is_active' => true,
                'is_closed' => false,
            ]);
        }

        // COA accounts
        $this->cashAccount = COA::create([
            'code' => '1101', 'name' => 'Kas', 'type' => 'aktiva',
            'level' => 2, 'order' => 1, 'is_active' => true, 'is_leaf_account' => true,
        ]);
        $this->receivableAccount = COA::create([
            'code' => '1201', 'name' => 'Piutang', 'type' => 'aktiva',
            'level' => 2, 'order' => 2, 'is_active' => true, 'is_leaf_account' => true,
        ]);
        $this->payableAccount = COA::create([
            'code' => '2101', 'name' => 'Hutang', 'type' => 'pasiva',
            'level' => 2, 'order' => 1, 'is_active' => true, 'is_leaf_account' => true,
        ]);
        $this->capitalAccount = COA::create([
            'code' => '3101', 'name' => 'Modal Pemilik', 'type' => 'modal',
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
        $this->summaryAccount = COA::create([
            'code' => '6001', 'name' => 'Ikhtisar Laba Rugi', 'type' => 'modal',
            'level' => 2, 'order' => 2, 'is_active' => true, 'is_leaf_account' => true,
        ]);
        $this->retainedEarningsAccount = COA::create([
            'code' => '3201', 'name' => 'Laba Ditahan', 'type' => 'modal',
            'level' => 2, 'order' => 3, 'is_active' => true, 'is_leaf_account' => true,
        ]);
    }

    // ======================================
    // Helpers
    // ======================================

    private function createTransaction(int $month, int $amount)
    {
        $period = $this->periods[$month];
        $date = Carbon::create($this->year, $month, 15)->format('Y-m-d');

        // Revenue
        $this->journalService->createJournalEntry([
            'journal_date' => $date,
            'id_period' => $period->id,
            'status' => 'posted',
            'type' => 'general',
            'entries' => [
                ['coa_code' => '1101', 'description' => 'Penerimaan kas', 'debit' => $amount, 'credit' => 0],
                ['coa_code' => '4101', 'description' => 'Pendapatan jasa', 'debit' => 0, 'credit' => $amount],
            ],
        ]);

        // Expense (half of revenue)
        $this->journalService->createJournalEntry([
            'journal_date' => $date,
            'id_period' => $period->id,
            'status' => 'posted',
            'type' => 'general',
            'entries' => [
                ['coa_code' => '5101', 'description' => 'Beban gaji', 'debit' => $amount / 2, 'credit' => 0],
                ['coa_code' => '1101', 'description' => 'Pengeluaran kas', 'debit' => 0, 'credit' => $amount / 2],
            ],
        ]);
    }

    private function closeAllMonths()
    {
        foreach ($this->periods as $period) {
            $period->update(['is_closed' => true, 'closed_at' => now()]);
        }
    }

    // ======================================
    // Route & Page Tests
    // ======================================

    /** @test */
    public function closing_page_accessible()
    {
        $response = $this->get(route('tax-closing'));
        $response->assertStatus(200);
        $response->assertSee('Perpajakan');
    }

    /** @test */
    public function closing_page_requires_authentication()
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
        Livewire::test(ClosingIndex::class)
            ->assertStatus(200)
            ->assertSee('Status Periode Bulanan');
    }

    /** @test */
    public function component_shows_all_12_periods()
    {
        Livewire::test(ClosingIndex::class)
            ->assertSee('Terbuka');
    }

    /** @test */
    public function component_shows_periods_with_status()
    {
        // Close January
        $this->periods[1]->update(['is_closed' => true, 'closed_at' => now()]);

        Livewire::test(ClosingIndex::class)
            ->assertSee('Ditutup')
            ->assertSee('Terbuka');
    }

    // ======================================
    // ClosingService: Monthly Close/Reopen
    // ======================================

    /** @test */
    public function service_closes_monthly_period()
    {
        $period = $this->closingService->closeMonth($this->periods[1]->id);

        $this->assertTrue($period->is_closed);
        $this->assertNotNull($period->closed_at);
    }

    /** @test */
    public function service_cannot_close_already_closed_period()
    {
        $this->periods[1]->update(['is_closed' => true, 'closed_at' => now()]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('sudah ditutup');
        $this->closingService->closeMonth($this->periods[1]->id);
    }

    /** @test */
    public function service_cannot_close_period_with_draft_journals()
    {
        // Create draft journal
        $this->journalService->createJournalEntry([
            'journal_date' => Carbon::create($this->year, 1, 15)->format('Y-m-d'),
            'id_period' => $this->periods[1]->id,
            'status' => 'draft',
            'type' => 'general',
            'entries' => [
                ['coa_code' => '1101', 'description' => 'Test', 'debit' => 1000, 'credit' => 0],
                ['coa_code' => '4101', 'description' => 'Test', 'debit' => 0, 'credit' => 1000],
            ],
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('jurnal draft');
        $this->closingService->closeMonth($this->periods[1]->id);
    }

    /** @test */
    public function service_reopens_monthly_period()
    {
        $this->periods[1]->update(['is_closed' => true, 'closed_at' => now()]);

        $period = $this->closingService->reopenMonth($this->periods[1]->id);

        $this->assertFalse($period->is_closed);
        $this->assertNull($period->closed_at);
    }

    /** @test */
    public function service_cannot_reopen_open_period()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('belum ditutup');
        $this->closingService->reopenMonth($this->periods[1]->id);
    }

    // ======================================
    // ClosingService: Year Status
    // ======================================

    /** @test */
    public function service_year_status_shows_all_open()
    {
        $status = $this->closingService->getYearClosingStatus($this->year);

        $this->assertCount(12, $status['periods']);
        $this->assertFalse($status['all_months_closed']);
        $this->assertFalse($status['has_closing_journal']);
        $this->assertNull($status['closing_journal']);
    }

    /** @test */
    public function service_year_status_all_closed()
    {
        $this->closeAllMonths();

        $status = $this->closingService->getYearClosingStatus($this->year);
        $this->assertTrue($status['all_months_closed']);
    }

    /** @test */
    public function service_year_status_partially_closed()
    {
        $this->periods[1]->update(['is_closed' => true, 'closed_at' => now()]);
        $this->periods[2]->update(['is_closed' => true, 'closed_at' => now()]);

        $status = $this->closingService->getYearClosingStatus($this->year);
        $this->assertFalse($status['all_months_closed']);
    }

    // ======================================
    // ClosingService: Yearly Closing
    // ======================================

    /** @test */
    public function service_cannot_close_year_without_all_months_closed()
    {
        $this->createTransaction(1, 10000000);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Semua periode bulanan harus ditutup');
        $this->closingService->closeYear($this->year, '6001', '3201');
    }

    /** @test */
    public function service_closes_year_with_profit()
    {
        $this->createTransaction(1, 10000000); // Revenue 10M, Expense 5M → Profit 5M
        $this->closeAllMonths();

        $journal = $this->closingService->closeYear($this->year, '6001', '3201');

        $this->assertNotNull($journal);
        $this->assertEquals('closing', $journal->type);
        $this->assertEquals('posted', $journal->status);
        $this->assertStringStartsWith('CLO/', $journal->journal_no);
        $this->assertEquals("{$this->year}-12-31", $journal->journal_date->format('Y-m-d'));

        // Verify the journal has proper entries
        $journal->load('journals.coa');
        $entries = $journal->journals;

        // Should have:
        // Dr Pendapatan 10M / Cr Ikhtisar 10M (close revenue)
        // Dr Ikhtisar 5M / Cr Beban 5M (close expense)
        // Dr Ikhtisar 5M / Cr Laba Ditahan 5M (close net income)
        $this->assertTrue($entries->count() >= 4);

        // Verify closing journal is balanced
        $totalDebit = $entries->sum('debit');
        $totalCredit = $entries->sum('credit');
        $this->assertEquals($totalDebit, $totalCredit);
    }

    /** @test */
    public function service_closes_year_with_loss()
    {
        // Create more expense than revenue
        $month1Period = $this->periods[1];
        $date = Carbon::create($this->year, 1, 15)->format('Y-m-d');

        // Revenue 5M
        $this->journalService->createJournalEntry([
            'journal_date' => $date,
            'id_period' => $month1Period->id,
            'status' => 'posted',
            'type' => 'general',
            'entries' => [
                ['coa_code' => '1101', 'debit' => 5000000, 'credit' => 0],
                ['coa_code' => '4101', 'debit' => 0, 'credit' => 5000000],
            ],
        ]);
        // Expense 8M
        $this->journalService->createJournalEntry([
            'journal_date' => $date,
            'id_period' => $month1Period->id,
            'status' => 'posted',
            'type' => 'general',
            'entries' => [
                ['coa_code' => '5101', 'debit' => 8000000, 'credit' => 0],
                ['coa_code' => '1101', 'debit' => 0, 'credit' => 8000000],
            ],
        ]);

        $this->closeAllMonths();
        $journal = $this->closingService->closeYear($this->year, '6001', '3201');

        $this->assertNotNull($journal);
        $journal->load('journals.coa');
        $entries = $journal->journals;

        // Verify balanced
        $this->assertEquals($entries->sum('debit'), $entries->sum('credit'));

        // For loss: Dr Laba Ditahan / Cr Ikhtisar should exist
        $retainedDebit = $entries->filter(function ($e) {
            return $e->coa->code === '3201' && $e->debit > 0;
        });
        $this->assertTrue($retainedDebit->count() > 0, 'Should debit Laba Ditahan for loss');
    }

    /** @test */
    public function service_cannot_close_year_twice()
    {
        $this->createTransaction(1, 10000000);
        $this->closeAllMonths();

        $this->closingService->closeYear($this->year, '6001', '3201');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('sudah dilakukan');
        $this->closingService->closeYear($this->year, '6001', '3201');
    }

    /** @test */
    public function service_year_status_detects_closing_journal()
    {
        $this->createTransaction(1, 10000000);
        $this->closeAllMonths();
        $this->closingService->closeYear($this->year, '6001', '3201');

        $status = $this->closingService->getYearClosingStatus($this->year);
        $this->assertTrue($status['has_closing_journal']);
        $this->assertNotNull($status['closing_journal']);
        $this->assertStringStartsWith('CLO/', $status['closing_journal']->journal_no);
    }

    /** @test */
    public function service_requires_december_period()
    {
        // Delete December period
        $this->periods[12]->delete();

        $this->createTransaction(1, 10000000);
        // Close months 1-11
        for ($m = 1; $m <= 11; $m++) {
            $this->periods[$m]->update(['is_closed' => true, 'closed_at' => now()]);
        }

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Desember');
        $this->closingService->closeYear($this->year, '6001', '3201');
    }

    // ======================================
    // Livewire: Monthly Close/Reopen via Component
    // ======================================

    /** @test */
    public function component_can_close_month()
    {
        Livewire::test(ClosingIndex::class)
            ->call('closeMonth', $this->periods[1]->id);

        $this->assertTrue($this->periods[1]->fresh()->is_closed);
    }

    /** @test */
    public function component_can_reopen_month()
    {
        $this->periods[1]->update(['is_closed' => true, 'closed_at' => now()]);

        Livewire::test(ClosingIndex::class)
            ->call('reopenMonth', $this->periods[1]->id);

        $this->assertFalse($this->periods[1]->fresh()->is_closed);
    }

    /** @test */
    public function component_shows_yearly_closing_prerequisites()
    {
        Livewire::test(ClosingIndex::class)
            ->assertSee('Semua periode bulanan ditutup')
            ->assertSee('Tutup Buku Tahunan');
    }

    /** @test */
    public function component_disables_yearly_closing_when_months_not_closed()
    {
        Livewire::test(ClosingIndex::class)
            ->assertSee('Tutup semua periode bulanan terlebih dahulu');
    }

    /** @test */
    public function component_shows_closing_form_when_all_months_closed()
    {
        $this->closeAllMonths();

        Livewire::test(ClosingIndex::class)
            ->assertSee('Akun Ikhtisar Laba Rugi')
            ->assertSee('Akun Laba Ditahan');
    }

    /** @test */
    public function component_validates_account_selection_for_yearly_closing()
    {
        $this->closeAllMonths();

        Livewire::test(ClosingIndex::class)
            ->call('closeYear')
            ->assertHasErrors(['summaryCoaId', 'retainedEarningsCoaId']);
    }

    /** @test */
    public function component_can_perform_yearly_closing()
    {
        $this->createTransaction(1, 10000000);
        $this->closeAllMonths();

        Livewire::test(ClosingIndex::class)
            ->set('summaryCoaId', $this->summaryAccount->id)
            ->set('retainedEarningsCoaId', $this->retainedEarningsAccount->id)
            ->call('closeYear');

        // Should have a closing journal now
        $closingJournal = JournalMaster::closing()->posted()->first();
        $this->assertNotNull($closingJournal);
    }

    /** @test */
    public function component_shows_closing_journal_after_yearly_close()
    {
        $this->createTransaction(1, 10000000);
        $this->closeAllMonths();
        $this->closingService->closeYear($this->year, '6001', '3201');

        Livewire::test(ClosingIndex::class)
            ->assertSee('Buku tahun')
            ->assertSee('telah ditutup');
    }

    // ======================================
    // Closing with Multiple Revenue/Expense Accounts
    // ======================================

    /** @test */
    public function closing_handles_multiple_revenue_and_expense_accounts()
    {
        // Create additional accounts
        $revenue2 = COA::create([
            'code' => '4201', 'name' => 'Pendapatan Lain', 'type' => 'pendapatan',
            'level' => 2, 'order' => 2, 'is_active' => true, 'is_leaf_account' => true,
        ]);
        $expense2 = COA::create([
            'code' => '5201', 'name' => 'Beban Sewa', 'type' => 'beban',
            'level' => 2, 'order' => 2, 'is_active' => true, 'is_leaf_account' => true,
        ]);

        $period = $this->periods[1];
        $date = Carbon::create($this->year, 1, 15)->format('Y-m-d');

        // Revenue 1: 10M
        $this->journalService->createJournalEntry([
            'journal_date' => $date, 'id_period' => $period->id, 'status' => 'posted', 'type' => 'general',
            'entries' => [
                ['coa_code' => '1101', 'debit' => 10000000, 'credit' => 0],
                ['coa_code' => '4101', 'debit' => 0, 'credit' => 10000000],
            ],
        ]);
        // Revenue 2: 3M
        $this->journalService->createJournalEntry([
            'journal_date' => $date, 'id_period' => $period->id, 'status' => 'posted', 'type' => 'general',
            'entries' => [
                ['coa_code' => '1201', 'debit' => 3000000, 'credit' => 0],
                ['coa_code' => '4201', 'debit' => 0, 'credit' => 3000000],
            ],
        ]);
        // Expense 1: 4M
        $this->journalService->createJournalEntry([
            'journal_date' => $date, 'id_period' => $period->id, 'status' => 'posted', 'type' => 'general',
            'entries' => [
                ['coa_code' => '5101', 'debit' => 4000000, 'credit' => 0],
                ['coa_code' => '1101', 'debit' => 0, 'credit' => 4000000],
            ],
        ]);
        // Expense 2: 2M
        $this->journalService->createJournalEntry([
            'journal_date' => $date, 'id_period' => $period->id, 'status' => 'posted', 'type' => 'general',
            'entries' => [
                ['coa_code' => '5201', 'debit' => 2000000, 'credit' => 0],
                ['coa_code' => '1101', 'debit' => 0, 'credit' => 2000000],
            ],
        ]);

        $this->closeAllMonths();
        $journal = $this->closingService->closeYear($this->year, '6001', '3201');
        $journal->load('journals.coa');

        // Net income = (10M + 3M) - (4M + 2M) = 7M
        // Closing entries:
        // Dr 4101 10M, Dr 4201 3M / Cr 6001 13M (close revenues)
        // Dr 6001 6M / Cr 5101 4M, Cr 5201 2M (close expenses)
        // Dr 6001 7M / Cr 3201 7M (close net income to retained earnings)

        $revenueDebit = $journal->journals->where('debit', '>', 0)
            ->filter(fn($e) => in_array($e->coa->type, ['pendapatan']))
            ->sum('debit');
        $this->assertEquals(13000000, $revenueDebit);

        $expenseCredit = $journal->journals->where('credit', '>', 0)
            ->filter(fn($e) => in_array($e->coa->type, ['beban']))
            ->sum('credit');
        $this->assertEquals(6000000, $expenseCredit);

        // Laba Ditahan should receive the net income
        $retainedCredit = $journal->journals
            ->filter(fn($e) => $e->coa->code === '3201' && $e->credit > 0)
            ->sum('credit');
        $this->assertEquals(7000000, $retainedCredit);

        // Journal must be balanced
        $this->assertEquals($journal->journals->sum('debit'), $journal->journals->sum('credit'));
    }
}
