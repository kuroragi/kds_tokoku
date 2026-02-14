<?php

namespace Tests\Feature;

use App\Models\COA;
use App\Models\Period;
use App\Models\User;
use App\Services\FinancialReportService;
use App\Services\JournalService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialReportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Period $currentPeriod;
    protected Period $prevPeriod;
    protected COA $cashAccount;
    protected COA $receivableAccount;
    protected COA $payableAccount;
    protected COA $capitalAccount;
    protected COA $revenueAccount;
    protected COA $expenseAccount;
    protected COA $expenseRentAccount;
    protected JournalService $journalService;
    protected FinancialReportService $reportService;

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

        // Aktiva
        $this->cashAccount = COA::create([
            'code' => '1101', 'name' => 'Kas', 'type' => 'aktiva',
            'level' => 2, 'order' => 1, 'is_active' => true, 'is_leaf_account' => true,
        ]);
        $this->receivableAccount = COA::create([
            'code' => '1201', 'name' => 'Piutang Dagang', 'type' => 'aktiva',
            'level' => 2, 'order' => 2, 'is_active' => true, 'is_leaf_account' => true,
        ]);

        // Pasiva
        $this->payableAccount = COA::create([
            'code' => '2101', 'name' => 'Hutang Dagang', 'type' => 'pasiva',
            'level' => 2, 'order' => 1, 'is_active' => true, 'is_leaf_account' => true,
        ]);

        // Modal
        $this->capitalAccount = COA::create([
            'code' => '3101', 'name' => 'Modal Pemilik', 'type' => 'modal',
            'level' => 2, 'order' => 1, 'is_active' => true, 'is_leaf_account' => true,
        ]);

        // Pendapatan
        $this->revenueAccount = COA::create([
            'code' => '4101', 'name' => 'Pendapatan Penjualan', 'type' => 'pendapatan',
            'level' => 2, 'order' => 1, 'is_active' => true, 'is_leaf_account' => true,
        ]);

        // Beban
        $this->expenseAccount = COA::create([
            'code' => '5101', 'name' => 'Beban Gaji', 'type' => 'beban',
            'level' => 2, 'order' => 1, 'is_active' => true, 'is_leaf_account' => true,
        ]);
        $this->expenseRentAccount = COA::create([
            'code' => '5102', 'name' => 'Beban Sewa', 'type' => 'beban',
            'level' => 2, 'order' => 2, 'is_active' => true, 'is_leaf_account' => true,
        ]);
    }

    private function createPostedJournal(array $entries, ?int $periodId = null, ?string $date = null)
    {
        return $this->journalService->createJournalEntry([
            'journal_date' => $date ?? now()->format('Y-m-d'),
            'id_period' => $periodId ?? $this->currentPeriod->id,
            'status' => 'posted',
            'entries' => $entries,
        ]);
    }

    private function seedBasicTransactions(): void
    {
        // Owner invests 10,000,000 cash
        $this->createPostedJournal([
            ['coa_code' => '1101', 'debit' => 10000000, 'credit' => 0],
            ['coa_code' => '3101', 'debit' => 0, 'credit' => 10000000],
        ]);

        // Sales revenue 5,000,000 cash
        $this->createPostedJournal([
            ['coa_code' => '1101', 'debit' => 5000000, 'credit' => 0],
            ['coa_code' => '4101', 'debit' => 0, 'credit' => 5000000],
        ]);

        // Pay salary 2,000,000
        $this->createPostedJournal([
            ['coa_code' => '5101', 'debit' => 2000000, 'credit' => 0],
            ['coa_code' => '1101', 'debit' => 0, 'credit' => 2000000],
        ]);

        // Pay rent 1,000,000
        $this->createPostedJournal([
            ['coa_code' => '5102', 'debit' => 1000000, 'credit' => 0],
            ['coa_code' => '1101', 'debit' => 0, 'credit' => 1000000],
        ]);
    }

    // ==========================================
    // Trial Balance Tests
    // ==========================================

    public function test_trial_balance_returns_empty_when_no_data(): void
    {
        $result = $this->reportService->getTrialBalance();

        $this->assertCount(0, $result['accounts']);
        $this->assertEquals(0, $result['total_debit']);
        $this->assertEquals(0, $result['total_credit']);
    }

    public function test_trial_balance_shows_correct_balances(): void
    {
        $this->seedBasicTransactions();

        $result = $this->reportService->getTrialBalance();

        // Kas: 10M + 5M - 2M - 1M = 12M debit
        $cash = $result['accounts']->firstWhere('coa_code', '1101');
        $this->assertEquals(12000000, $cash->saldo_debit);
        $this->assertEquals(0, $cash->saldo_credit);

        // Modal: 10M credit
        $capital = $result['accounts']->firstWhere('coa_code', '3101');
        $this->assertEquals(0, $capital->saldo_debit);
        $this->assertEquals(10000000, $capital->saldo_credit);

        // Pendapatan: 5M credit
        $revenue = $result['accounts']->firstWhere('coa_code', '4101');
        $this->assertEquals(0, $revenue->saldo_debit);
        $this->assertEquals(5000000, $revenue->saldo_credit);

        // Beban Gaji: 2M debit
        $gaji = $result['accounts']->firstWhere('coa_code', '5101');
        $this->assertEquals(2000000, $gaji->saldo_debit);
        $this->assertEquals(0, $gaji->saldo_credit);

        // Beban Sewa: 1M debit
        $sewa = $result['accounts']->firstWhere('coa_code', '5102');
        $this->assertEquals(1000000, $sewa->saldo_debit);
        $this->assertEquals(0, $sewa->saldo_credit);
    }

    public function test_trial_balance_is_balanced(): void
    {
        $this->seedBasicTransactions();

        $result = $this->reportService->getTrialBalance();

        // total debit should equal total credit
        $this->assertEquals($result['total_debit'], $result['total_credit']);
        // 12M (kas) + 2M (gaji) + 1M (sewa) = 15M debit
        // 10M (modal) + 5M (pendapatan) = 15M credit
        $this->assertEquals(15000000, $result['total_debit']);
    }

    public function test_trial_balance_excludes_draft_journals(): void
    {
        // Draft journal (not posted)
        $this->journalService->createJournalEntry([
            'journal_date' => now()->format('Y-m-d'),
            'id_period' => $this->currentPeriod->id,
            'entries' => [
                ['coa_code' => '1101', 'debit' => 500000, 'credit' => 0],
                ['coa_code' => '4101', 'debit' => 0, 'credit' => 500000],
            ],
        ]);

        $result = $this->reportService->getTrialBalance();
        $this->assertCount(0, $result['accounts']);
    }

    public function test_trial_balance_filters_by_period(): void
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

        $result = $this->reportService->getTrialBalance([
            'period_id' => $this->currentPeriod->id,
        ]);

        $cash = $result['accounts']->firstWhere('coa_code', '1101');
        $this->assertEquals(1000000, $cash->saldo_debit);
    }

    public function test_trial_balance_filters_by_date_range(): void
    {
        $this->createPostedJournal([
            ['coa_code' => '1101', 'debit' => 1000000, 'credit' => 0],
            ['coa_code' => '4101', 'debit' => 0, 'credit' => 1000000],
        ], $this->currentPeriod->id, now()->format('Y-m-') . '10');

        $this->createPostedJournal([
            ['coa_code' => '1101', 'debit' => 500000, 'credit' => 0],
            ['coa_code' => '4101', 'debit' => 0, 'credit' => 500000],
        ], $this->currentPeriod->id, now()->format('Y-m-') . '01');

        $result = $this->reportService->getTrialBalance([
            'date_from' => now()->format('Y-m-') . '10',
            'date_to' => now()->format('Y-m-') . '10',
        ]);

        $cash = $result['accounts']->firstWhere('coa_code', '1101');
        $this->assertEquals(1000000, $cash->saldo_debit);
    }

    // ==========================================
    // Balance Sheet Tests
    // ==========================================

    public function test_balance_sheet_returns_correct_structure(): void
    {
        $this->seedBasicTransactions();

        $result = $this->reportService->getBalanceSheet();

        $this->assertArrayHasKey('aktiva', $result);
        $this->assertArrayHasKey('pasiva', $result);
        $this->assertArrayHasKey('modal', $result);
        $this->assertArrayHasKey('laba_rugi', $result);
        $this->assertArrayHasKey('total_aktiva', $result);
        $this->assertArrayHasKey('total_pasiva_modal_laba', $result);
        $this->assertArrayHasKey('is_balanced', $result);
    }

    public function test_balance_sheet_aktiva_is_correct(): void
    {
        $this->seedBasicTransactions();

        $result = $this->reportService->getBalanceSheet();

        // Kas: 10M + 5M - 2M - 1M = 12M
        $this->assertEquals(12000000, $result['total_aktiva']);
        $this->assertCount(1, $result['aktiva']); // only cash has transactions
    }

    public function test_balance_sheet_pasiva_modal_correct(): void
    {
        $this->seedBasicTransactions();

        $result = $this->reportService->getBalanceSheet();

        $this->assertEquals(0, $result['total_pasiva']); // no payable transactions
        $this->assertEquals(10000000, $result['total_modal']);
    }

    public function test_balance_sheet_includes_laba_rugi(): void
    {
        $this->seedBasicTransactions();

        $result = $this->reportService->getBalanceSheet();

        // Laba = 5M pendapatan - (2M gaji + 1M sewa) = 2M
        $this->assertEquals(2000000, $result['laba_rugi']);
    }

    public function test_balance_sheet_is_balanced(): void
    {
        $this->seedBasicTransactions();

        $result = $this->reportService->getBalanceSheet();

        // Aktiva = 12M
        // Pasiva(0) + Modal(10M) + Laba(2M) = 12M
        $this->assertTrue($result['is_balanced']);
        $this->assertEquals($result['total_aktiva'], $result['total_pasiva_modal_laba']);
    }

    public function test_balance_sheet_with_payable(): void
    {
        $this->seedBasicTransactions();

        // Buy on credit: 3M
        $this->createPostedJournal([
            ['coa_code' => '1201', 'debit' => 3000000, 'credit' => 0],
            ['coa_code' => '2101', 'debit' => 0, 'credit' => 3000000],
        ]);

        $result = $this->reportService->getBalanceSheet();

        // Aktiva: 12M(kas) + 3M(piutang) = 15M
        $this->assertEquals(15000000, $result['total_aktiva']);
        // Pasiva = 3M, Modal = 10M, Laba = 2M → 15M
        $this->assertEquals(3000000, $result['total_pasiva']);
        $this->assertTrue($result['is_balanced']);
    }

    public function test_balance_sheet_filters_by_period(): void
    {
        $this->createPostedJournal([
            ['coa_code' => '1101', 'debit' => 1000000, 'credit' => 0],
            ['coa_code' => '3101', 'debit' => 0, 'credit' => 1000000],
        ], $this->currentPeriod->id);

        $prevDate = now()->subMonth()->format('Y-m-') . '15';
        $this->createPostedJournal([
            ['coa_code' => '1101', 'debit' => 5000000, 'credit' => 0],
            ['coa_code' => '3101', 'debit' => 0, 'credit' => 5000000],
        ], $this->prevPeriod->id, $prevDate);

        $result = $this->reportService->getBalanceSheet([
            'period_id' => $this->currentPeriod->id,
        ]);

        $this->assertEquals(1000000, $result['total_aktiva']);
    }

    // ==========================================
    // Income Statement Tests
    // ==========================================

    public function test_income_statement_returns_correct_structure(): void
    {
        $this->seedBasicTransactions();

        $result = $this->reportService->getIncomeStatement();

        $this->assertArrayHasKey('pendapatan', $result);
        $this->assertArrayHasKey('beban', $result);
        $this->assertArrayHasKey('total_pendapatan', $result);
        $this->assertArrayHasKey('total_beban', $result);
        $this->assertArrayHasKey('net_income', $result);
        $this->assertArrayHasKey('is_profit', $result);
    }

    public function test_income_statement_calculates_pendapatan(): void
    {
        $this->seedBasicTransactions();

        $result = $this->reportService->getIncomeStatement();

        $this->assertEquals(5000000, $result['total_pendapatan']);
        $this->assertCount(1, $result['pendapatan']);
    }

    public function test_income_statement_calculates_beban(): void
    {
        $this->seedBasicTransactions();

        $result = $this->reportService->getIncomeStatement();

        $this->assertEquals(3000000, $result['total_beban']); // 2M gaji + 1M sewa
        $this->assertCount(2, $result['beban']);
    }

    public function test_income_statement_calculates_net_income(): void
    {
        $this->seedBasicTransactions();

        $result = $this->reportService->getIncomeStatement();

        $this->assertEquals(2000000, $result['net_income']); // 5M - 3M = 2M
        $this->assertTrue($result['is_profit']);
    }

    public function test_income_statement_detects_loss(): void
    {
        // Revenue 1M
        $this->createPostedJournal([
            ['coa_code' => '1101', 'debit' => 1000000, 'credit' => 0],
            ['coa_code' => '4101', 'debit' => 0, 'credit' => 1000000],
        ]);

        // Expense 3M
        $this->createPostedJournal([
            ['coa_code' => '5101', 'debit' => 3000000, 'credit' => 0],
            ['coa_code' => '1101', 'debit' => 0, 'credit' => 3000000],
        ]);

        $result = $this->reportService->getIncomeStatement();

        $this->assertEquals(-2000000, $result['net_income']);
        $this->assertFalse($result['is_profit']);
    }

    public function test_income_statement_empty_when_no_data(): void
    {
        $result = $this->reportService->getIncomeStatement();

        $this->assertCount(0, $result['pendapatan']);
        $this->assertCount(0, $result['beban']);
        $this->assertEquals(0, $result['net_income']);
    }

    public function test_income_statement_excludes_non_income_accounts(): void
    {
        $this->seedBasicTransactions();

        $result = $this->reportService->getIncomeStatement();

        // Should not include aktiva, pasiva, modal
        $allCodes = $result['pendapatan']->pluck('coa_code')
            ->merge($result['beban']->pluck('coa_code'));

        $this->assertFalse($allCodes->contains('1101'));
        $this->assertFalse($allCodes->contains('3101'));
    }

    public function test_income_statement_filters_by_period(): void
    {
        $this->createPostedJournal([
            ['coa_code' => '1101', 'debit' => 5000000, 'credit' => 0],
            ['coa_code' => '4101', 'debit' => 0, 'credit' => 5000000],
        ], $this->currentPeriod->id);

        $prevDate = now()->subMonth()->format('Y-m-') . '15';
        $this->createPostedJournal([
            ['coa_code' => '1101', 'debit' => 1000000, 'credit' => 0],
            ['coa_code' => '4101', 'debit' => 0, 'credit' => 1000000],
        ], $this->prevPeriod->id, $prevDate);

        $result = $this->reportService->getIncomeStatement([
            'period_id' => $this->currentPeriod->id,
        ]);

        $this->assertEquals(5000000, $result['total_pendapatan']);
    }

    public function test_income_statement_filters_by_date_range(): void
    {
        $this->createPostedJournal([
            ['coa_code' => '1101', 'debit' => 3000000, 'credit' => 0],
            ['coa_code' => '4101', 'debit' => 0, 'credit' => 3000000],
        ], $this->currentPeriod->id, now()->format('Y-m-') . '10');

        $this->createPostedJournal([
            ['coa_code' => '1101', 'debit' => 1000000, 'credit' => 0],
            ['coa_code' => '4101', 'debit' => 0, 'credit' => 1000000],
        ], $this->currentPeriod->id, now()->format('Y-m-') . '01');

        $result = $this->reportService->getIncomeStatement([
            'date_from' => now()->format('Y-m-') . '05',
            'date_to' => now()->format('Y-m-') . '15',
        ]);

        $this->assertEquals(3000000, $result['total_pendapatan']);
    }

    // ==========================================
    // Integration: Balance Sheet vs Income Statement
    // ==========================================

    public function test_balance_sheet_laba_matches_income_statement(): void
    {
        $this->seedBasicTransactions();

        $balanceSheet = $this->reportService->getBalanceSheet();
        $incomeStatement = $this->reportService->getIncomeStatement();

        $this->assertEquals($incomeStatement['net_income'], $balanceSheet['laba_rugi']);
    }
}
