<?php

namespace Tests\Feature;

use App\Models\COA;
use App\Models\Period;
use App\Models\User;
use App\Services\JournalService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportPdfTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Period $currentPeriod;
    protected JournalService $journalService;
    protected COA $cashAccount;
    protected COA $capitalAccount;
    protected COA $revenueAccount;
    protected COA $expenseAccount;
    protected COA $payableAccount;

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
            'code' => '4101', 'name' => 'Pendapatan Jasa', 'type' => 'pendapatan',
            'level' => 2, 'order' => 1, 'is_active' => true, 'is_leaf_account' => true,
        ]);
        $this->expenseAccount = COA::create([
            'code' => '5101', 'name' => 'Beban Gaji', 'type' => 'beban',
            'level' => 2, 'order' => 1, 'is_active' => true, 'is_leaf_account' => true,
        ]);
    }

    private function createSampleJournal(): void
    {
        $this->journalService->createJournalEntry([
            'journal_date' => now()->format('Y-m-d'),
            'description' => 'Investasi modal awal',
            'id_period' => $this->currentPeriod->id,
            'type' => 'general',
            'status' => 'posted',
            'entries' => [
                ['coa_code' => '1101', 'description' => 'Kas masuk', 'debit' => 10000000, 'credit' => 0],
                ['coa_code' => '3101', 'description' => 'Modal pemilik', 'debit' => 0, 'credit' => 10000000],
            ],
        ]);

        $this->journalService->createJournalEntry([
            'journal_date' => now()->format('Y-m-d'),
            'description' => 'Pendapatan jasa',
            'id_period' => $this->currentPeriod->id,
            'type' => 'general',
            'status' => 'posted',
            'entries' => [
                ['coa_code' => '1101', 'description' => 'Kas masuk', 'debit' => 5000000, 'credit' => 0],
                ['coa_code' => '4101', 'description' => 'Pendapatan jasa', 'debit' => 0, 'credit' => 5000000],
            ],
        ]);

        $this->journalService->createJournalEntry([
            'journal_date' => now()->format('Y-m-d'),
            'description' => 'Bayar gaji',
            'id_period' => $this->currentPeriod->id,
            'type' => 'general',
            'status' => 'posted',
            'entries' => [
                ['coa_code' => '5101', 'description' => 'Beban gaji', 'debit' => 2000000, 'credit' => 0],
                ['coa_code' => '1101', 'description' => 'Kas keluar', 'debit' => 0, 'credit' => 2000000],
            ],
        ]);
    }

    // =============================================
    // TRIAL BALANCE PDF
    // =============================================

    /** @test */
    public function trial_balance_pdf_requires_filter()
    {
        $response = $this->get(route('report.pdf.trial-balance'));

        $response->assertRedirect();
    }

    /** @test */
    public function trial_balance_pdf_download_by_period()
    {
        $this->createSampleJournal();

        $response = $this->get(route('report.pdf.trial-balance', [
            'period_id' => $this->currentPeriod->id,
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    /** @test */
    public function trial_balance_pdf_download_by_date_range()
    {
        $this->createSampleJournal();

        $response = $this->get(route('report.pdf.trial-balance', [
            'date_from' => now()->startOfMonth()->format('Y-m-d'),
            'date_to' => now()->endOfMonth()->format('Y-m-d'),
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    // =============================================
    // BALANCE SHEET PDF
    // =============================================

    /** @test */
    public function balance_sheet_pdf_requires_filter()
    {
        $response = $this->get(route('report.pdf.balance-sheet'));

        $response->assertRedirect();
    }

    /** @test */
    public function balance_sheet_pdf_download_by_period()
    {
        $this->createSampleJournal();

        $response = $this->get(route('report.pdf.balance-sheet', [
            'period_id' => $this->currentPeriod->id,
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    /** @test */
    public function balance_sheet_pdf_download_by_date_range()
    {
        $this->createSampleJournal();

        $response = $this->get(route('report.pdf.balance-sheet', [
            'date_from' => now()->startOfMonth()->format('Y-m-d'),
            'date_to' => now()->endOfMonth()->format('Y-m-d'),
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    // =============================================
    // INCOME STATEMENT PDF
    // =============================================

    /** @test */
    public function income_statement_pdf_requires_filter()
    {
        $response = $this->get(route('report.pdf.income-statement'));

        $response->assertRedirect();
    }

    /** @test */
    public function income_statement_pdf_download_by_period()
    {
        $this->createSampleJournal();

        $response = $this->get(route('report.pdf.income-statement', [
            'period_id' => $this->currentPeriod->id,
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    /** @test */
    public function income_statement_pdf_download_by_date_range()
    {
        $this->createSampleJournal();

        $response = $this->get(route('report.pdf.income-statement', [
            'date_from' => now()->startOfMonth()->format('Y-m-d'),
            'date_to' => now()->endOfMonth()->format('Y-m-d'),
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    // =============================================
    // ADJUSTED TRIAL BALANCE PDF
    // =============================================

    /** @test */
    public function adjusted_trial_balance_pdf_requires_filter()
    {
        $response = $this->get(route('report.pdf.adjusted-trial-balance'));

        $response->assertRedirect();
    }

    /** @test */
    public function adjusted_trial_balance_pdf_download_by_period()
    {
        $this->createSampleJournal();

        $response = $this->get(route('report.pdf.adjusted-trial-balance', [
            'period_id' => $this->currentPeriod->id,
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    // =============================================
    // GENERAL LEDGER PDF
    // =============================================

    /** @test */
    public function general_ledger_pdf_download_all()
    {
        $this->createSampleJournal();

        $response = $this->get(route('report.pdf.general-ledger', [
            'period_id' => $this->currentPeriod->id,
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    /** @test */
    public function general_ledger_pdf_download_by_coa_type()
    {
        $this->createSampleJournal();

        $response = $this->get(route('report.pdf.general-ledger', [
            'period_id' => $this->currentPeriod->id,
            'coa_type' => 'aktiva',
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    /** @test */
    public function general_ledger_pdf_download_by_date_range()
    {
        $this->createSampleJournal();

        $response = $this->get(route('report.pdf.general-ledger', [
            'date_from' => now()->startOfMonth()->format('Y-m-d'),
            'date_to' => now()->endOfMonth()->format('Y-m-d'),
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    // =============================================
    // GENERAL LEDGER DETAIL PDF
    // =============================================

    /** @test */
    public function general_ledger_detail_pdf_download()
    {
        $this->createSampleJournal();

        $response = $this->get(route('report.pdf.general-ledger.detail', [
            'coa' => $this->cashAccount->id,
            'period_id' => $this->currentPeriod->id,
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    /** @test */
    public function general_ledger_detail_pdf_download_by_date()
    {
        $this->createSampleJournal();

        $response = $this->get(route('report.pdf.general-ledger.detail', [
            'coa' => $this->revenueAccount->id,
            'date_from' => now()->startOfMonth()->format('Y-m-d'),
            'date_to' => now()->endOfMonth()->format('Y-m-d'),
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    // =============================================
    // VALIDATION TESTS
    // =============================================

    /** @test */
    public function trial_balance_pdf_validates_date_range()
    {
        $response = $this->get(route('report.pdf.trial-balance', [
            'date_from' => '2026-02-28',
            'date_to' => '2026-02-01',
        ]));

        $response->assertSessionHasErrors('date_to');
    }

    /** @test */
    public function income_statement_pdf_validates_invalid_period()
    {
        $response = $this->get(route('report.pdf.income-statement', [
            'period_id' => 99999,
        ]));

        $response->assertSessionHasErrors('period_id');
    }

    /** @test */
    public function general_ledger_pdf_validates_coa_type()
    {
        $response = $this->get(route('report.pdf.general-ledger', [
            'period_id' => $this->currentPeriod->id,
            'coa_type' => 'invalid_type',
        ]));

        $response->assertSessionHasErrors('coa_type');
    }

    // =============================================
    // AUTH TESTS
    // =============================================

    /** @test */
    public function unauthenticated_user_cannot_download_pdf()
    {
        auth()->logout();

        $routes = [
            route('report.pdf.trial-balance', ['period_id' => $this->currentPeriod->id]),
            route('report.pdf.balance-sheet', ['period_id' => $this->currentPeriod->id]),
            route('report.pdf.income-statement', ['period_id' => $this->currentPeriod->id]),
            route('report.pdf.adjusted-trial-balance', ['period_id' => $this->currentPeriod->id]),
            route('report.pdf.general-ledger', ['period_id' => $this->currentPeriod->id]),
        ];

        foreach ($routes as $url) {
            $response = $this->get($url);
            $response->assertRedirect(route('login'));
        }
    }

    // =============================================
    // LIVEWIRE DOWNLOAD URL TESTS
    // =============================================

    /** @test */
    public function trial_balance_livewire_has_download_urls()
    {
        \Livewire\Livewire::test(\App\Livewire\TrialBalance\TrialBalanceIndex::class)
            ->set('filterPeriod', $this->currentPeriod->id)
            ->call('generateReport')
            ->assertSee('Download Neraca (PDF)')
            ->assertSee('Download Neraca Saldo (PDF)');
    }

    /** @test */
    public function income_statement_livewire_has_download_url()
    {
        $this->createSampleJournal();

        \Livewire\Livewire::test(\App\Livewire\IncomeStatement\IncomeStatementIndex::class)
            ->set('filterPeriod', $this->currentPeriod->id)
            ->call('generateReport')
            ->assertSee('Download PDF');
    }

    /** @test */
    public function adjusted_trial_balance_livewire_has_download_url()
    {
        $this->createSampleJournal();

        \Livewire\Livewire::test(\App\Livewire\AdjustedTrialBalance\AdjustedTrialBalanceIndex::class)
            ->set('filterPeriod', $this->currentPeriod->id)
            ->call('generateReport')
            ->assertSee('Download PDF');
    }

    /** @test */
    public function general_ledger_livewire_has_download_url()
    {
        $this->createSampleJournal();

        \Livewire\Livewire::test(\App\Livewire\GeneralLedger\GeneralLedgerIndex::class)
            ->assertSee('Download PDF');
    }

    /** @test */
    public function general_ledger_detail_livewire_has_download_url()
    {
        $this->createSampleJournal();

        \Livewire\Livewire::test(\App\Livewire\GeneralLedger\GeneralLedgerDetail::class, ['coa' => $this->cashAccount])
            ->assertSee('PDF');
    }

    // =============================================
    // EMPTY DATA TESTS
    // =============================================

    /** @test */
    public function trial_balance_pdf_with_no_data()
    {
        $response = $this->get(route('report.pdf.trial-balance', [
            'period_id' => $this->currentPeriod->id,
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    /** @test */
    public function general_ledger_detail_pdf_with_no_transactions()
    {
        $response = $this->get(route('report.pdf.general-ledger.detail', [
            'coa' => $this->cashAccount->id,
            'period_id' => $this->currentPeriod->id,
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }
}
