<?php

namespace Tests\Feature;

use App\Models\COA;
use App\Models\Period;
use App\Models\User;
use App\Services\JournalService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FinalBalanceSheetTest extends TestCase
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
        // Modal investment: Dr Kas 10M / Cr Modal 10M
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

        // Revenue: Dr Kas 5M / Cr Pendapatan 5M
        $this->journalService->createJournalEntry([
            'journal_date' => now()->format('Y-m-d'),
            'description' => 'Pendapatan jasa',
            'id_period' => $this->currentPeriod->id,
            'type' => 'general',
            'status' => 'posted',
            'entries' => [
                ['coa_code' => '1101', 'description' => 'Kas masuk', 'debit' => 5000000, 'credit' => 0],
                ['coa_code' => '4101', 'description' => 'Jasa konsultasi', 'debit' => 0, 'credit' => 5000000],
            ],
        ]);

        // Expense: Dr Beban 2M / Cr Kas 2M
        $this->journalService->createJournalEntry([
            'journal_date' => now()->format('Y-m-d'),
            'description' => 'Pembayaran gaji',
            'id_period' => $this->currentPeriod->id,
            'type' => 'general',
            'status' => 'posted',
            'entries' => [
                ['coa_code' => '5101', 'description' => 'Beban gaji', 'debit' => 2000000, 'credit' => 0],
                ['coa_code' => '1101', 'description' => 'Kas keluar', 'debit' => 0, 'credit' => 2000000],
            ],
        ]);
    }

    // ==========================================
    // PAGE ACCESS TESTS
    // ==========================================

    /** @test */
    public function page_requires_authentication()
    {
        auth()->logout();

        $this->get(route('report.final-balance-sheet'))
            ->assertRedirect(route('login'));
    }

    /** @test */
    public function page_is_accessible_for_authenticated_user()
    {
        $this->get(route('report.final-balance-sheet'))
            ->assertOk()
            ->assertSee('Neraca Keuangan Final');
    }

    /** @test */
    public function page_contains_livewire_component()
    {
        $this->get(route('report.final-balance-sheet'))
            ->assertOk()
            ->assertSeeLivewire('report.final-balance-sheet');
    }

    // ==========================================
    // LIVEWIRE COMPONENT TESTS
    // ==========================================

    /** @test */
    public function livewire_shows_filter_controls()
    {
        Livewire::test(\App\Livewire\Report\FinalBalanceSheet::class)
            ->assertSee('Pilih Periode')
            ->assertSee('Dari Tanggal')
            ->assertSee('Sampai Tanggal')
            ->assertSee('Tampilkan');
    }

    /** @test */
    public function livewire_requires_filter_before_generating()
    {
        Livewire::test(\App\Livewire\Report\FinalBalanceSheet::class)
            ->call('generateReport')
            ->assertSet('showReport', false)
            ->assertDispatched('alert');
    }

    /** @test */
    public function livewire_generates_report_with_period_filter()
    {
        $this->createSampleJournal();

        Livewire::test(\App\Livewire\Report\FinalBalanceSheet::class)
            ->set('filterPeriod', $this->currentPeriod->id)
            ->call('generateReport')
            ->assertSet('showReport', true)
            ->assertSee('AKTIVA')
            ->assertSee('PASIVA')
            ->assertSee('MODAL')
            ->assertSee('PENDAPATAN')
            ->assertSee('BEBAN');
    }

    /** @test */
    public function livewire_generates_report_with_date_range_filter()
    {
        $this->createSampleJournal();

        Livewire::test(\App\Livewire\Report\FinalBalanceSheet::class)
            ->set('dateFrom', now()->startOfMonth()->format('Y-m-d'))
            ->set('dateTo', now()->endOfMonth()->format('Y-m-d'))
            ->call('generateReport')
            ->assertSet('showReport', true)
            ->assertSee('Kas');
    }

    /** @test */
    public function livewire_shows_balance_sheet_data_correctly()
    {
        $this->createSampleJournal();

        // After journals: Kas = 10M+5M-2M = 13M, Modal = 10M, Pendapatan = 5M, Beban = 2M
        // Balance sheet: Aktiva = 13M, Pasiva = 0, Modal = 10M, L/R = 3M
        // 13M = 0 + 10M + 3M → balanced
        Livewire::test(\App\Livewire\Report\FinalBalanceSheet::class)
            ->set('filterPeriod', $this->currentPeriod->id)
            ->call('generateReport')
            ->assertSee('13.000.000')    // Total Aktiva
            ->assertSee('10.000.000')    // Total Modal
            ->assertSee('Neraca SEIMBANG');
    }

    /** @test */
    public function livewire_shows_income_statement_data_correctly()
    {
        $this->createSampleJournal();

        Livewire::test(\App\Livewire\Report\FinalBalanceSheet::class)
            ->set('filterPeriod', $this->currentPeriod->id)
            ->call('generateReport')
            ->assertSee('5.000.000')     // Total Pendapatan
            ->assertSee('2.000.000')     // Total Beban
            ->assertSee('3.000.000');    // Net Income
    }

    /** @test */
    public function livewire_shows_accounting_equation()
    {
        $this->createSampleJournal();

        Livewire::test(\App\Livewire\Report\FinalBalanceSheet::class)
            ->set('filterPeriod', $this->currentPeriod->id)
            ->call('generateReport')
            ->assertSee('Ringkasan Posisi Keuangan')
            ->assertSee('BALANCE');
    }

    /** @test */
    public function livewire_clear_filters_resets_state()
    {
        Livewire::test(\App\Livewire\Report\FinalBalanceSheet::class)
            ->set('filterPeriod', $this->currentPeriod->id)
            ->call('generateReport')
            ->assertSet('showReport', true)
            ->call('clearFilters')
            ->assertSet('showReport', false)
            ->assertSet('filterPeriod', '')
            ->assertSet('dateFrom', '')
            ->assertSet('dateTo', '');
    }

    /** @test */
    public function livewire_has_download_url()
    {
        Livewire::test(\App\Livewire\Report\FinalBalanceSheet::class)
            ->set('filterPeriod', $this->currentPeriod->id)
            ->call('generateReport')
            ->assertSee('Download PDF');
    }

    /** @test */
    public function livewire_shows_periods_in_filter()
    {
        Livewire::test(\App\Livewire\Report\FinalBalanceSheet::class)
            ->assertSee($this->currentPeriod->period_name);
    }

    /** @test */
    public function livewire_shows_empty_state_before_filter()
    {
        Livewire::test(\App\Livewire\Report\FinalBalanceSheet::class)
            ->assertSee('Pilih periode atau range tanggal');
    }

    // ==========================================
    // PDF DOWNLOAD TESTS
    // ==========================================

    /** @test */
    public function pdf_download_requires_filter()
    {
        $this->get(route('report.pdf.final-balance-sheet'))
            ->assertRedirect();
    }

    /** @test */
    public function pdf_download_by_period()
    {
        $this->createSampleJournal();

        $response = $this->get(route('report.pdf.final-balance-sheet', [
            'period_id' => $this->currentPeriod->id,
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('neraca-keuangan-final', $response->headers->get('content-disposition'));
    }

    /** @test */
    public function pdf_download_by_date_range()
    {
        $this->createSampleJournal();

        $response = $this->get(route('report.pdf.final-balance-sheet', [
            'date_from' => now()->startOfMonth()->format('Y-m-d'),
            'date_to' => now()->endOfMonth()->format('Y-m-d'),
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    /** @test */
    public function pdf_download_validates_dates()
    {
        $this->get(route('report.pdf.final-balance-sheet', [
            'date_from' => '2026-01-31',
            'date_to' => '2026-01-01',
        ]))->assertSessionHasErrors('date_to');
    }

    /** @test */
    public function pdf_download_validates_period_exists()
    {
        $this->get(route('report.pdf.final-balance-sheet', [
            'period_id' => 99999,
        ]))->assertSessionHasErrors('period_id');
    }

    /** @test */
    public function pdf_download_requires_authentication()
    {
        auth()->logout();

        $this->get(route('report.pdf.final-balance-sheet', [
            'period_id' => $this->currentPeriod->id,
        ]))->assertRedirect(route('login'));
    }

    /** @test */
    public function pdf_download_with_empty_data()
    {
        // No journals created — should still generate a PDF  
        $response = $this->get(route('report.pdf.final-balance-sheet', [
            'period_id' => $this->currentPeriod->id,
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    // ==========================================
    // DOWNLOAD URL INTEGRATION TESTS
    // ==========================================

    /** @test */
    public function download_url_contains_correct_route()
    {
        $component = Livewire::test(\App\Livewire\Report\FinalBalanceSheet::class)
            ->set('filterPeriod', $this->currentPeriod->id);

        $downloadUrl = $component->viewData('downloadUrl');
        $this->assertStringContainsString('report/pdf/final-balance-sheet', $downloadUrl);
        $this->assertStringContainsString('period_id=' . $this->currentPeriod->id, $downloadUrl);
    }

    /** @test */
    public function download_url_with_date_range()
    {
        $component = Livewire::test(\App\Livewire\Report\FinalBalanceSheet::class)
            ->set('dateFrom', '2026-01-01')
            ->set('dateTo', '2026-01-31');

        $downloadUrl = $component->viewData('downloadUrl');
        $this->assertStringContainsString('date_from=2026-01-01', $downloadUrl);
        $this->assertStringContainsString('date_to=2026-01-31', $downloadUrl);
    }

    // ==========================================
    // BALANCE CALCULATION TESTS
    // ==========================================

    /** @test */
    public function report_shows_balanced_state_when_balanced()
    {
        $this->createSampleJournal();

        Livewire::test(\App\Livewire\Report\FinalBalanceSheet::class)
            ->set('filterPeriod', $this->currentPeriod->id)
            ->call('generateReport')
            ->assertSee('Neraca SEIMBANG')
            ->assertSee('BALANCE');
    }

    /** @test */
    public function report_calculates_net_income_correctly()
    {
        $this->createSampleJournal();

        // Pendapatan = 5M, Beban = 2M, Net Income = 3M
        $component = Livewire::test(\App\Livewire\Report\FinalBalanceSheet::class)
            ->set('filterPeriod', $this->currentPeriod->id)
            ->call('generateReport');

        $incomeData = $component->viewData('incomeStatementData');
        $this->assertEquals(5000000, $incomeData['total_pendapatan']);
        $this->assertEquals(2000000, $incomeData['total_beban']);
        $this->assertEquals(3000000, $incomeData['net_income']);
        $this->assertTrue($incomeData['is_profit']);
    }

    /** @test */
    public function report_balance_sheet_totals_correct()
    {
        $this->createSampleJournal();

        $component = Livewire::test(\App\Livewire\Report\FinalBalanceSheet::class)
            ->set('filterPeriod', $this->currentPeriod->id)
            ->call('generateReport');

        $bsData = $component->viewData('balanceSheetData');
        $this->assertEquals(13000000, $bsData['total_aktiva']);
        $this->assertEquals(0, $bsData['total_pasiva']);
        $this->assertEquals(10000000, $bsData['total_modal']);
        $this->assertEquals(3000000, $bsData['laba_rugi']);
        $this->assertTrue($bsData['is_balanced']);
    }

    /** @test */
    public function report_shows_loss_when_expenses_exceed_revenue()
    {
        // Create journal with more expenses than revenue
        // Invest: Dr Kas 10M / Cr Modal 10M
        $this->journalService->createJournalEntry([
            'journal_date' => now()->format('Y-m-d'),
            'description' => 'Modal awal',
            'id_period' => $this->currentPeriod->id,
            'type' => 'general',
            'status' => 'posted',
            'entries' => [
                ['coa_code' => '1101', 'description' => 'Kas', 'debit' => 10000000, 'credit' => 0],
                ['coa_code' => '3101', 'description' => 'Modal', 'debit' => 0, 'credit' => 10000000],
            ],
        ]);

        // Revenue: Dr Kas 1M / Cr Pendapatan 1M
        $this->journalService->createJournalEntry([
            'journal_date' => now()->format('Y-m-d'),
            'description' => 'Pendapatan kecil',
            'id_period' => $this->currentPeriod->id,
            'type' => 'general',
            'status' => 'posted',
            'entries' => [
                ['coa_code' => '1101', 'description' => 'Kas', 'debit' => 1000000, 'credit' => 0],
                ['coa_code' => '4101', 'description' => 'Pendapatan', 'debit' => 0, 'credit' => 1000000],
            ],
        ]);

        // Expense: Dr Beban 3M / Cr Kas 3M
        $this->journalService->createJournalEntry([
            'journal_date' => now()->format('Y-m-d'),
            'description' => 'Beban besar',
            'id_period' => $this->currentPeriod->id,
            'type' => 'general',
            'status' => 'posted',
            'entries' => [
                ['coa_code' => '5101', 'description' => 'Beban', 'debit' => 3000000, 'credit' => 0],
                ['coa_code' => '1101', 'description' => 'Kas', 'debit' => 0, 'credit' => 3000000],
            ],
        ]);

        $component = Livewire::test(\App\Livewire\Report\FinalBalanceSheet::class)
            ->set('filterPeriod', $this->currentPeriod->id)
            ->call('generateReport');

        $incomeData = $component->viewData('incomeStatementData');
        $this->assertFalse($incomeData['is_profit']);
        $this->assertEquals(-2000000, $incomeData['net_income']);

        $component->assertSee('RUGI BERSIH');
    }
}
