<?php

namespace Tests\Feature;

use App\Models\COA;
use App\Models\Journal;
use App\Models\JournalMaster;
use App\Models\Period;
use App\Models\User;
use App\Services\JournalService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JournalServiceTest extends TestCase
{
    use RefreshDatabase;

    protected JournalService $service;
    protected User $user;
    protected Period $period;
    protected COA $cashAccount;
    protected COA $revenueAccount;
    protected COA $receivableAccount;
    protected COA $payableAccount;
    protected COA $inventoryAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new JournalService();

        // Create user without triggering Blameable events, then authenticate
        $this->user = User::withoutEvents(function () {
            return User::factory()->create();
        });
        $this->actingAs($this->user);

        // Create current period
        $now = Carbon::now();
        $this->period = Period::create([
            'code' => $now->format('Ym'),
            'name' => $now->translatedFormat('F') . ' ' . $now->year,
            'start_date' => $now->copy()->startOfMonth()->format('Y-m-d'),
            'end_date' => $now->copy()->endOfMonth()->format('Y-m-d'),
            'year' => $now->year,
            'month' => $now->month,
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

        $this->receivableAccount = COA::create([
            'code' => '1201',
            'name' => 'Piutang Dagang',
            'type' => 'aktiva',
            'level' => 2,
            'order' => 2,
            'is_active' => true,
            'is_leaf_account' => true,
        ]);

        $this->inventoryAccount = COA::create([
            'code' => '1301',
            'name' => 'Persediaan Barang',
            'type' => 'aktiva',
            'level' => 2,
            'order' => 3,
            'is_active' => true,
            'is_leaf_account' => true,
        ]);

        $this->payableAccount = COA::create([
            'code' => '2101',
            'name' => 'Hutang Dagang',
            'type' => 'pasiva',
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
    }

    // ==========================================
    // createJournalEntry Tests
    // ==========================================

    public function test_can_create_balanced_journal_entry(): void
    {
        $result = $this->service->createJournalEntry([
            'journal_date' => now()->format('Y-m-d'),
            'reference' => 'REF-001',
            'description' => 'Test journal entry',
            'id_period' => $this->period->id,
            'entries' => [
                ['coa_code' => '1101', 'description' => 'Cash', 'debit' => 1000000, 'credit' => 0],
                ['coa_code' => '4101', 'description' => 'Revenue', 'debit' => 0, 'credit' => 1000000],
            ],
        ]);

        $this->assertInstanceOf(JournalMaster::class, $result);
        $this->assertEquals(1000000, $result->total_debit);
        $this->assertEquals(1000000, $result->total_credit);
        $this->assertEquals('draft', $result->status);
        $this->assertCount(2, $result->journals);
        $this->assertDatabaseCount('journal_masters', 1);
        $this->assertDatabaseCount('journals', 2);
    }

    public function test_create_journal_generates_correct_number(): void
    {
        $result = $this->service->createJournalEntry([
            'journal_date' => now()->format('Y-m-d'),
            'id_period' => $this->period->id,
            'entries' => [
                ['coa_code' => '1101', 'debit' => 500000, 'credit' => 0],
                ['coa_code' => '4101', 'debit' => 0, 'credit' => 500000],
            ],
        ]);

        $expectedPrefix = 'JRN/' . now()->format('Y') . '/' . now()->format('m') . '/0001';
        $this->assertEquals($expectedPrefix, $result->journal_no);
    }

    public function test_journal_number_increments(): void
    {
        // Create first journal
        $this->service->createJournalEntry([
            'journal_date' => now()->format('Y-m-d'),
            'id_period' => $this->period->id,
            'entries' => [
                ['coa_code' => '1101', 'debit' => 100000, 'credit' => 0],
                ['coa_code' => '4101', 'debit' => 0, 'credit' => 100000],
            ],
        ]);

        // Create second journal
        $result = $this->service->createJournalEntry([
            'journal_date' => now()->format('Y-m-d'),
            'id_period' => $this->period->id,
            'entries' => [
                ['coa_code' => '1101', 'debit' => 200000, 'credit' => 0],
                ['coa_code' => '4101', 'debit' => 0, 'credit' => 200000],
            ],
        ]);

        $this->assertStringEndsWith('/0002', $result->journal_no);
    }

    public function test_create_journal_with_posted_status(): void
    {
        $result = $this->service->createJournalEntry([
            'journal_date' => now()->format('Y-m-d'),
            'id_period' => $this->period->id,
            'status' => 'posted',
            'entries' => [
                ['coa_code' => '1101', 'debit' => 100000, 'credit' => 0],
                ['coa_code' => '4101', 'debit' => 0, 'credit' => 100000],
            ],
        ]);

        $this->assertEquals('posted', $result->status);
        $this->assertNotNull($result->posted_at);
    }

    public function test_create_journal_fails_when_unbalanced(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('not balanced');

        $this->service->createJournalEntry([
            'journal_date' => now()->format('Y-m-d'),
            'id_period' => $this->period->id,
            'entries' => [
                ['coa_code' => '1101', 'debit' => 1000000, 'credit' => 0],
                ['coa_code' => '4101', 'debit' => 0, 'credit' => 500000],
            ],
        ]);
    }

    public function test_create_journal_fails_without_date(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('date is required');

        $this->service->createJournalEntry([
            'journal_date' => '',
            'id_period' => $this->period->id,
            'entries' => [
                ['coa_code' => '1101', 'debit' => 100000, 'credit' => 0],
                ['coa_code' => '4101', 'debit' => 0, 'credit' => 100000],
            ],
        ]);
    }

    public function test_create_journal_fails_without_period(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Period is required');

        $this->service->createJournalEntry([
            'journal_date' => now()->format('Y-m-d'),
            'id_period' => '',
            'entries' => [
                ['coa_code' => '1101', 'debit' => 100000, 'credit' => 0],
                ['coa_code' => '4101', 'debit' => 0, 'credit' => 100000],
            ],
        ]);
    }

    public function test_create_journal_fails_with_less_than_two_entries(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('At least 2');

        $this->service->createJournalEntry([
            'journal_date' => now()->format('Y-m-d'),
            'id_period' => $this->period->id,
            'entries' => [
                ['coa_code' => '1101', 'debit' => 100000, 'credit' => 0],
            ],
        ]);
    }

    public function test_create_journal_fails_with_invalid_coa_code(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('not found');

        $this->service->createJournalEntry([
            'journal_date' => now()->format('Y-m-d'),
            'id_period' => $this->period->id,
            'entries' => [
                ['coa_code' => '9999', 'debit' => 100000, 'credit' => 0],
                ['coa_code' => '4101', 'debit' => 0, 'credit' => 100000],
            ],
        ]);
    }

    public function test_create_journal_fails_with_negative_amounts(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('cannot be negative');

        $this->service->createJournalEntry([
            'journal_date' => now()->format('Y-m-d'),
            'id_period' => $this->period->id,
            'entries' => [
                ['coa_code' => '1101', 'debit' => -100000, 'credit' => 0],
                ['coa_code' => '4101', 'debit' => 0, 'credit' => 100000],
            ],
        ]);
    }

    public function test_create_journal_fails_with_both_debit_and_credit(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('cannot have both debit and credit');

        $this->service->createJournalEntry([
            'journal_date' => now()->format('Y-m-d'),
            'id_period' => $this->period->id,
            'entries' => [
                ['coa_code' => '1101', 'debit' => 100000, 'credit' => 100000],
                ['coa_code' => '4101', 'debit' => 0, 'credit' => 100000],
            ],
        ]);
    }

    public function test_create_journal_rollsback_on_error(): void
    {
        try {
            $this->service->createJournalEntry([
                'journal_date' => now()->format('Y-m-d'),
                'id_period' => $this->period->id,
                'entries' => [
                    ['coa_code' => '1101', 'debit' => 100000, 'credit' => 0],
                    ['coa_code' => 'INVALID', 'debit' => 0, 'credit' => 100000],
                ],
            ]);
        } catch (\Exception $e) {
            // Expected
        }

        $this->assertDatabaseCount('journal_masters', 0);
        $this->assertDatabaseCount('journals', 0);
    }

    // ==========================================
    // Shortcut Method Tests
    // ==========================================

    public function test_can_create_sales_journal(): void
    {
        $result = $this->service->createSalesJournal([
            'date' => now()->format('Y-m-d'),
            'invoice_no' => 'INV-001',
            'description' => 'Sales to Customer A',
            'customer_name' => 'Customer A',
            'id_period' => $this->period->id,
            'amount' => 5000000,
        ]);

        $this->assertInstanceOf(JournalMaster::class, $result);
        $this->assertEquals(5000000, $result->total_debit);
        $this->assertEquals(5000000, $result->total_credit);
        $this->assertEquals('INV-001', $result->reference);

        // Check entries
        $debitEntry = $result->journals->where('debit', '>', 0)->first();
        $creditEntry = $result->journals->where('credit', '>', 0)->first();

        $this->assertEquals('1201', $debitEntry->coa->code); // Piutang Dagang
        $this->assertEquals('4101', $creditEntry->coa->code); // Pendapatan Penjualan
    }

    public function test_can_create_purchase_journal(): void
    {
        $result = $this->service->createPurchaseJournal([
            'date' => now()->format('Y-m-d'),
            'purchase_no' => 'PO-001',
            'description' => 'Purchase from Supplier B',
            'supplier_name' => 'Supplier B',
            'id_period' => $this->period->id,
            'amount' => 3000000,
        ]);

        $this->assertInstanceOf(JournalMaster::class, $result);
        $this->assertEquals(3000000, $result->total_debit);
        $this->assertEquals(3000000, $result->total_credit);

        $debitEntry = $result->journals->where('debit', '>', 0)->first();
        $creditEntry = $result->journals->where('credit', '>', 0)->first();

        $this->assertEquals('1301', $debitEntry->coa->code); // Persediaan Barang
        $this->assertEquals('2101', $creditEntry->coa->code); // Hutang Dagang
    }

    public function test_can_create_payment_journal(): void
    {
        $result = $this->service->createPaymentJournal([
            'date' => now()->format('Y-m-d'),
            'payment_no' => 'PAY-001',
            'description' => 'Payment to Supplier B',
            'payee_name' => 'Supplier B',
            'id_period' => $this->period->id,
            'amount' => 3000000,
        ]);

        $this->assertInstanceOf(JournalMaster::class, $result);
        $this->assertEquals(3000000, $result->total_debit);
        $this->assertEquals(3000000, $result->total_credit);

        $debitEntry = $result->journals->where('debit', '>', 0)->first();
        $creditEntry = $result->journals->where('credit', '>', 0)->first();

        $this->assertEquals('2101', $debitEntry->coa->code); // Hutang Dagang
        $this->assertEquals('1101', $creditEntry->coa->code); // Kas di Tangan
    }

    public function test_sales_journal_can_auto_post(): void
    {
        $result = $this->service->createSalesJournal([
            'date' => now()->format('Y-m-d'),
            'id_period' => $this->period->id,
            'amount' => 1000000,
            'auto_post' => true,
        ]);

        $this->assertEquals('posted', $result->status);
        $this->assertNotNull($result->posted_at);
    }

    // ==========================================
    // Post Journal Tests
    // ==========================================

    public function test_can_post_draft_journal(): void
    {
        $journal = $this->service->createJournalEntry([
            'journal_date' => now()->format('Y-m-d'),
            'id_period' => $this->period->id,
            'entries' => [
                ['coa_code' => '1101', 'debit' => 100000, 'credit' => 0],
                ['coa_code' => '4101', 'debit' => 0, 'credit' => 100000],
            ],
        ]);

        $this->assertEquals('draft', $journal->status);

        $result = $this->service->postJournal($journal->id);

        $this->assertEquals('posted', $result->status);
        $this->assertNotNull($result->posted_at);
    }

    public function test_cannot_post_already_posted_journal(): void
    {
        $journal = $this->service->createJournalEntry([
            'journal_date' => now()->format('Y-m-d'),
            'id_period' => $this->period->id,
            'status' => 'posted',
            'entries' => [
                ['coa_code' => '1101', 'debit' => 100000, 'credit' => 0],
                ['coa_code' => '4101', 'debit' => 0, 'credit' => 100000],
            ],
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('already posted');

        $this->service->postJournal($journal->id);
    }

    // ==========================================
    // Journal Detail Sequence Tests
    // ==========================================

    public function test_journal_entries_have_correct_sequence(): void
    {
        $result = $this->service->createJournalEntry([
            'journal_date' => now()->format('Y-m-d'),
            'id_period' => $this->period->id,
            'entries' => [
                ['coa_code' => '1101', 'debit' => 300000, 'credit' => 0],
                ['coa_code' => '1201', 'debit' => 200000, 'credit' => 0],
                ['coa_code' => '4101', 'debit' => 0, 'credit' => 500000],
            ],
        ]);

        $journals = $result->journals->sortBy('sequence');
        $this->assertEquals(1, $journals->first()->sequence);
        $this->assertEquals(3, $journals->last()->sequence);
    }

    public function test_journal_loads_relationships(): void
    {
        $result = $this->service->createJournalEntry([
            'journal_date' => now()->format('Y-m-d'),
            'id_period' => $this->period->id,
            'entries' => [
                ['coa_code' => '1101', 'debit' => 100000, 'credit' => 0],
                ['coa_code' => '4101', 'debit' => 0, 'credit' => 100000],
            ],
        ]);

        // Should have relationships loaded
        $this->assertTrue($result->relationLoaded('journals'));
        $this->assertTrue($result->relationLoaded('period'));
        $this->assertTrue($result->journals->first()->relationLoaded('coa'));
    }
}
