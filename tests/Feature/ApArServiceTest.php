<?php

namespace Tests\Feature;

use App\Models\BusinessUnit;
use App\Models\BusinessUnitCoaMapping;
use App\Models\COA;
use App\Models\Customer;
use App\Models\Payable;
use App\Models\PayablePayment;
use App\Models\Period;
use App\Models\Receivable;
use App\Models\ReceivablePayment;
use App\Models\User;
use App\Models\Vendor;
use App\Services\ApArService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApArServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected BusinessUnit $unit;
    protected ApArService $service;
    protected Period $period;
    protected COA $cashCoa;
    protected COA $hutangUsahaCoa;
    protected COA $hutangPajakCoa;
    protected COA $piutangUsahaCoa;
    protected COA $bebanCoa;
    protected COA $pendapatanCoa;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'superadmin']);

        $this->user = User::withoutEvents(fn() => User::factory()->create());
        $this->user->assignRole('superadmin');
        $this->actingAs($this->user);

        $this->unit = BusinessUnit::withoutEvents(fn() => BusinessUnit::create([
            'code' => 'UNT-001', 'name' => 'Test Unit', 'is_active' => true,
        ]));

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

        // COA accounts
        $this->cashCoa = COA::create([
            'code' => '1101', 'name' => 'Kas', 'type' => 'aktiva',
            'level' => 2, 'order' => 1, 'is_active' => true, 'is_leaf_account' => true,
        ]);

        $this->hutangUsahaCoa = COA::create([
            'code' => '2101', 'name' => 'Hutang Usaha', 'type' => 'pasiva',
            'level' => 2, 'order' => 1, 'is_active' => true, 'is_leaf_account' => true,
        ]);

        $this->hutangPajakCoa = COA::create([
            'code' => '2102', 'name' => 'Hutang Pajak', 'type' => 'pasiva',
            'level' => 2, 'order' => 2, 'is_active' => true, 'is_leaf_account' => true,
        ]);

        $this->piutangUsahaCoa = COA::create([
            'code' => '1201', 'name' => 'Piutang Usaha', 'type' => 'aktiva',
            'level' => 2, 'order' => 2, 'is_active' => true, 'is_leaf_account' => true,
        ]);

        $this->bebanCoa = COA::create([
            'code' => '5101', 'name' => 'Beban Jasa Notaris', 'type' => 'beban',
            'level' => 2, 'order' => 1, 'is_active' => true, 'is_leaf_account' => true,
        ]);

        $this->pendapatanCoa = COA::create([
            'code' => '4101', 'name' => 'Pendapatan Utama', 'type' => 'pendapatan',
            'level' => 2, 'order' => 1, 'is_active' => true, 'is_leaf_account' => true,
        ]);

        // COA Mappings for BusinessUnit
        BusinessUnitCoaMapping::create([
            'business_unit_id' => $this->unit->id,
            'account_key' => 'hutang_usaha',
            'label' => 'Hutang Usaha',
            'coa_id' => $this->hutangUsahaCoa->id,
        ]);
        BusinessUnitCoaMapping::create([
            'business_unit_id' => $this->unit->id,
            'account_key' => 'hutang_pajak',
            'label' => 'Hutang Pajak',
            'coa_id' => $this->hutangPajakCoa->id,
        ]);
        BusinessUnitCoaMapping::create([
            'business_unit_id' => $this->unit->id,
            'account_key' => 'piutang_usaha',
            'label' => 'Piutang Usaha',
            'coa_id' => $this->piutangUsahaCoa->id,
        ]);

        $this->service = app(ApArService::class);
    }

    protected function createVendor(array $overrides = []): Vendor
    {
        return Vendor::withoutEvents(function () use ($overrides) {
            $vendor = Vendor::create(array_merge([
                'code' => 'VND-001',
                'name' => 'Vendor Test',
                'type' => 'distributor',
                'is_active' => true,
                'is_pph23' => false,
                'pph23_rate' => 0,
                'is_net_pph23' => false,
            ], $overrides));
            $vendor->businessUnits()->attach($this->unit->id);
            return $vendor;
        });
    }

    protected function createCustomer(array $overrides = []): Customer
    {
        return Customer::withoutEvents(function () use ($overrides) {
            return Customer::create(array_merge([
                'business_unit_id' => $this->unit->id,
                'code' => 'CST-001',
                'name' => 'Customer Test',
                'is_active' => true,
            ], $overrides));
        });
    }

    // ==================== PPH23 CALCULATION TESTS ====================

    /** @test */
    public function pph23_calculation_normal_basis_2_percent()
    {
        // Input 10,000,000, rate 2%, normal = DPP is input
        $result = ApArService::calculatePph23(10000000, 2.00, false);

        $this->assertEquals(10000000, $result['dpp']);
        $this->assertEquals(200000, $result['pph23_amount']); // 10M * 2%
        $this->assertEquals(9800000, $result['amount_due']); // 10M - 200K
    }

    /** @test */
    public function pph23_calculation_net_basis_2_percent()
    {
        // Vendor mau terima net 3,000,000 → DPP = 3,000,000 / 0.98
        $result = ApArService::calculatePph23(3000000, 2.00, true);

        $expectedDpp = (int) round(3000000 / 0.98); // 3,061,224 (rounded)
        $expectedPph23 = $expectedDpp - 3000000;

        $this->assertEquals($expectedDpp, $result['dpp']);
        $this->assertEquals($expectedPph23, $result['pph23_amount']);
        $this->assertEquals(3000000, $result['amount_due']); // vendor receives exactly net
    }

    /** @test */
    public function pph23_calculation_with_zero_rate()
    {
        $result = ApArService::calculatePph23(5000000, 0, false);

        $this->assertEquals(5000000, $result['dpp']);
        $this->assertEquals(0, $result['pph23_amount']);
        $this->assertEquals(5000000, $result['amount_due']);
    }

    /** @test */
    public function pph23_calculation_net_basis_with_zero_rate()
    {
        $result = ApArService::calculatePph23(5000000, 0, true);

        $this->assertEquals(5000000, $result['dpp']);
        $this->assertEquals(0, $result['pph23_amount']);
        $this->assertEquals(5000000, $result['amount_due']);
    }

    /** @test */
    public function pph23_calculation_normal_basis_15_percent()
    {
        // Testing with a different PPh23 rate (15% for dividends etc.)
        $result = ApArService::calculatePph23(10000000, 15.00, false);

        $this->assertEquals(10000000, $result['dpp']);
        $this->assertEquals(1500000, $result['pph23_amount']);
        $this->assertEquals(8500000, $result['amount_due']);
    }

    /** @test */
    public function pph23_calculation_net_basis_15_percent()
    {
        $result = ApArService::calculatePph23(8500000, 15.00, true);

        $expectedDpp = (int) round(8500000 / 0.85); // 10,000,000
        $expectedPph23 = $expectedDpp - 8500000;

        $this->assertEquals($expectedDpp, $result['dpp']);
        $this->assertEquals($expectedPph23, $result['pph23_amount']);
        $this->assertEquals(8500000, $result['amount_due']);
    }

    // ==================== CREATE PAYABLE TESTS ====================

    /** @test */
    public function can_create_payable_without_pph23()
    {
        $vendor = $this->createVendor();

        $payable = $this->service->createPayable([
            'business_unit_id' => $this->unit->id,
            'vendor_id' => $vendor->id,
            'invoice_number' => 'INV-001',
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'description' => 'Jasa notaris',
            'debit_coa_id' => $this->bebanCoa->id,
            'input_amount' => 5000000,
        ]);

        $this->assertDatabaseHas('payables', [
            'invoice_number' => 'INV-001',
            'input_amount' => 5000000,
            'dpp' => 5000000,
            'pph23_amount' => 0,
            'amount_due' => 5000000,
            'status' => 'unpaid',
            'paid_amount' => 0,
        ]);

        // Journal should be created
        $this->assertNotNull($payable->journal_master_id);
    }

    /** @test */
    public function can_create_payable_with_pph23_normal_basis()
    {
        $vendor = $this->createVendor([
            'is_pph23' => true,
            'pph23_rate' => 2.00,
            'is_net_pph23' => false,
        ]);

        $payable = $this->service->createPayable([
            'business_unit_id' => $this->unit->id,
            'vendor_id' => $vendor->id,
            'invoice_number' => 'INV-002',
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'debit_coa_id' => $this->bebanCoa->id,
            'input_amount' => 10000000,
        ]);

        $this->assertDatabaseHas('payables', [
            'invoice_number' => 'INV-002',
            'input_amount' => 10000000,
            'dpp' => 10000000,
            'pph23_amount' => 200000,
            'amount_due' => 9800000,
            'is_net_basis' => false,
        ]);

        $this->assertNotNull($payable->journal_master_id);
    }

    /** @test */
    public function can_create_payable_with_pph23_net_basis()
    {
        $vendor = $this->createVendor([
            'is_pph23' => true,
            'pph23_rate' => 2.00,
            'is_net_pph23' => true,
        ]);

        $payable = $this->service->createPayable([
            'business_unit_id' => $this->unit->id,
            'vendor_id' => $vendor->id,
            'invoice_number' => 'INV-003',
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'debit_coa_id' => $this->bebanCoa->id,
            'input_amount' => 3000000, // vendor wants net 3M
        ]);

        $expectedDpp = (int) round(3000000 / 0.98);
        $expectedPph23 = $expectedDpp - 3000000;

        $this->assertDatabaseHas('payables', [
            'invoice_number' => 'INV-003',
            'input_amount' => 3000000,
            'dpp' => $expectedDpp,
            'pph23_amount' => $expectedPph23,
            'amount_due' => 3000000, // vendor gets exactly net amount
            'is_net_basis' => true,
        ]);
    }

    /** @test */
    public function create_payable_requires_debit_coa()
    {
        $vendor = $this->createVendor();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Akun debit (beban/aset) harus dipilih.');

        $this->service->createPayable([
            'business_unit_id' => $this->unit->id,
            'vendor_id' => $vendor->id,
            'invoice_number' => 'INV-ERR',
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'debit_coa_id' => null,
            'input_amount' => 1000000,
        ]);
    }

    /** @test */
    public function create_payable_fails_without_active_period()
    {
        // Close the period
        $this->period->update(['is_closed' => true]);

        $vendor = $this->createVendor();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Tidak ada periode aktif yang terbuka.');

        $this->service->createPayable([
            'business_unit_id' => $this->unit->id,
            'vendor_id' => $vendor->id,
            'invoice_number' => 'INV-ERR2',
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'debit_coa_id' => $this->bebanCoa->id,
            'input_amount' => 1000000,
        ]);
    }

    // ==================== VOID PAYABLE TESTS ====================

    /** @test */
    public function can_void_unpaid_payable()
    {
        $vendor = $this->createVendor();
        $payable = $this->service->createPayable([
            'business_unit_id' => $this->unit->id,
            'vendor_id' => $vendor->id,
            'invoice_number' => 'INV-VOID',
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'debit_coa_id' => $this->bebanCoa->id,
            'input_amount' => 5000000,
        ]);

        $result = $this->service->voidPayable($payable);

        $this->assertEquals('void', $result->status);
        $this->assertDatabaseHas('payables', [
            'id' => $payable->id,
            'status' => 'void',
        ]);
    }

    /** @test */
    public function cannot_void_payable_with_payments()
    {
        $vendor = $this->createVendor();
        $payable = $this->service->createPayable([
            'business_unit_id' => $this->unit->id,
            'vendor_id' => $vendor->id,
            'invoice_number' => 'INV-PAID',
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'debit_coa_id' => $this->bebanCoa->id,
            'input_amount' => 5000000,
        ]);

        // Make a payment
        $this->service->createPayablePayment($payable, [
            'payment_date' => now()->format('Y-m-d'),
            'amount' => 1000000,
            'payment_coa_id' => $this->cashCoa->id,
            'reference' => 'PAY-001',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Tidak dapat membatalkan hutang yang sudah ada pembayaran.');

        $this->service->voidPayable($payable->fresh());
    }

    // ==================== PAYABLE PAYMENT TESTS ====================

    /** @test */
    public function can_make_partial_payment()
    {
        $vendor = $this->createVendor();
        $payable = $this->service->createPayable([
            'business_unit_id' => $this->unit->id,
            'vendor_id' => $vendor->id,
            'invoice_number' => 'INV-PART',
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'debit_coa_id' => $this->bebanCoa->id,
            'input_amount' => 10000000,
        ]);

        $payment = $this->service->createPayablePayment($payable, [
            'payment_date' => now()->format('Y-m-d'),
            'amount' => 3000000,
            'payment_coa_id' => $this->cashCoa->id,
            'reference' => 'PAY-001',
        ]);

        $payable->refresh();

        $this->assertEquals(3000000, $payable->paid_amount);
        $this->assertEquals('partial', $payable->status);
        $this->assertEquals(7000000, $payable->remaining);
        $this->assertNotNull($payment->journal_master_id);
    }

    /** @test */
    public function can_make_full_payment()
    {
        $vendor = $this->createVendor();
        $payable = $this->service->createPayable([
            'business_unit_id' => $this->unit->id,
            'vendor_id' => $vendor->id,
            'invoice_number' => 'INV-FULL',
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'debit_coa_id' => $this->bebanCoa->id,
            'input_amount' => 5000000,
        ]);

        $this->service->createPayablePayment($payable, [
            'payment_date' => now()->format('Y-m-d'),
            'amount' => 5000000,
            'payment_coa_id' => $this->cashCoa->id,
        ]);

        $payable->refresh();

        $this->assertEquals(5000000, $payable->paid_amount);
        $this->assertEquals('paid', $payable->status);
        $this->assertEquals(0, $payable->remaining);
    }

    /** @test */
    public function can_make_multiple_partial_payments_to_full()
    {
        $vendor = $this->createVendor();
        $payable = $this->service->createPayable([
            'business_unit_id' => $this->unit->id,
            'vendor_id' => $vendor->id,
            'invoice_number' => 'INV-MULTI',
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'debit_coa_id' => $this->bebanCoa->id,
            'input_amount' => 9000000,
        ]);

        // First partial
        $this->service->createPayablePayment($payable, [
            'payment_date' => now()->format('Y-m-d'),
            'amount' => 3000000,
            'payment_coa_id' => $this->cashCoa->id,
        ]);
        $payable->refresh();
        $this->assertEquals('partial', $payable->status);

        // Second partial
        $this->service->createPayablePayment($payable, [
            'payment_date' => now()->format('Y-m-d'),
            'amount' => 3000000,
            'payment_coa_id' => $this->cashCoa->id,
        ]);
        $payable->refresh();
        $this->assertEquals('partial', $payable->status);
        $this->assertEquals(6000000, $payable->paid_amount);

        // Final payment
        $this->service->createPayablePayment($payable, [
            'payment_date' => now()->format('Y-m-d'),
            'amount' => 3000000,
            'payment_coa_id' => $this->cashCoa->id,
        ]);
        $payable->refresh();
        $this->assertEquals('paid', $payable->status);
        $this->assertEquals(9000000, $payable->paid_amount);
    }

    /** @test */
    public function cannot_pay_more_than_remaining()
    {
        $vendor = $this->createVendor();
        $payable = $this->service->createPayable([
            'business_unit_id' => $this->unit->id,
            'vendor_id' => $vendor->id,
            'invoice_number' => 'INV-OVER',
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'debit_coa_id' => $this->bebanCoa->id,
            'input_amount' => 5000000,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Jumlah pembayaran melebihi sisa hutang');

        $this->service->createPayablePayment($payable, [
            'payment_date' => now()->format('Y-m-d'),
            'amount' => 6000000,
            'payment_coa_id' => $this->cashCoa->id,
        ]);
    }

    /** @test */
    public function cannot_pay_zero_amount()
    {
        $vendor = $this->createVendor();
        $payable = $this->service->createPayable([
            'business_unit_id' => $this->unit->id,
            'vendor_id' => $vendor->id,
            'invoice_number' => 'INV-ZERO',
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'debit_coa_id' => $this->bebanCoa->id,
            'input_amount' => 5000000,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Jumlah pembayaran harus lebih dari 0.');

        $this->service->createPayablePayment($payable, [
            'payment_date' => now()->format('Y-m-d'),
            'amount' => 0,
            'payment_coa_id' => $this->cashCoa->id,
        ]);
    }

    /** @test */
    public function cannot_pay_already_paid_payable()
    {
        $vendor = $this->createVendor();
        $payable = $this->service->createPayable([
            'business_unit_id' => $this->unit->id,
            'vendor_id' => $vendor->id,
            'invoice_number' => 'INV-DPAID',
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'debit_coa_id' => $this->bebanCoa->id,
            'input_amount' => 1000000,
        ]);

        // Pay in full
        $this->service->createPayablePayment($payable, [
            'payment_date' => now()->format('Y-m-d'),
            'amount' => 1000000,
            'payment_coa_id' => $this->cashCoa->id,
        ]);

        $payable->refresh();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Hutang ini sudah lunas.');

        $this->service->createPayablePayment($payable, [
            'payment_date' => now()->format('Y-m-d'),
            'amount' => 500000,
            'payment_coa_id' => $this->cashCoa->id,
        ]);
    }

    /** @test */
    public function cannot_pay_voided_payable()
    {
        $vendor = $this->createVendor();
        $payable = $this->service->createPayable([
            'business_unit_id' => $this->unit->id,
            'vendor_id' => $vendor->id,
            'invoice_number' => 'INV-VOID-PAY',
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'debit_coa_id' => $this->bebanCoa->id,
            'input_amount' => 1000000,
        ]);

        $this->service->voidPayable($payable);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Hutang ini sudah dibatalkan.');

        $this->service->createPayablePayment($payable, [
            'payment_date' => now()->format('Y-m-d'),
            'amount' => 500000,
            'payment_coa_id' => $this->cashCoa->id,
        ]);
    }

    // ==================== PAYABLE WITH PPH23 PAYMENT TESTS ====================

    /** @test */
    public function pph23_payable_payment_uses_amount_due_not_dpp()
    {
        $vendor = $this->createVendor([
            'is_pph23' => true,
            'pph23_rate' => 2.00,
            'is_net_pph23' => false,
        ]);

        $payable = $this->service->createPayable([
            'business_unit_id' => $this->unit->id,
            'vendor_id' => $vendor->id,
            'invoice_number' => 'INV-PPH',
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'debit_coa_id' => $this->bebanCoa->id,
            'input_amount' => 10000000,
        ]);

        // amount_due = 9,800,000 (10M - 2% PPh23)
        $this->assertEquals(9800000, $payable->amount_due);

        // Full payment of amount_due
        $this->service->createPayablePayment($payable, [
            'payment_date' => now()->format('Y-m-d'),
            'amount' => 9800000,
            'payment_coa_id' => $this->cashCoa->id,
        ]);

        $payable->refresh();
        $this->assertEquals('paid', $payable->status);
    }

    /** @test */
    public function net_basis_payable_full_payment_equals_input_amount()
    {
        $vendor = $this->createVendor([
            'is_pph23' => true,
            'pph23_rate' => 2.00,
            'is_net_pph23' => true,
        ]);

        $payable = $this->service->createPayable([
            'business_unit_id' => $this->unit->id,
            'vendor_id' => $vendor->id,
            'invoice_number' => 'INV-NET',
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'debit_coa_id' => $this->bebanCoa->id,
            'input_amount' => 3000000,
        ]);

        // Net basis: amount_due = input_amount = 3,000,000
        $this->assertEquals(3000000, $payable->amount_due);

        $this->service->createPayablePayment($payable, [
            'payment_date' => now()->format('Y-m-d'),
            'amount' => 3000000,
            'payment_coa_id' => $this->cashCoa->id,
        ]);

        $payable->refresh();
        $this->assertEquals('paid', $payable->status);
    }

    // ==================== CREATE RECEIVABLE TESTS ====================

    /** @test */
    public function can_create_receivable()
    {
        $customer = $this->createCustomer();

        $receivable = $this->service->createReceivable([
            'business_unit_id' => $this->unit->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'AR-001',
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'description' => 'Penjualan barang',
            'credit_coa_id' => $this->pendapatanCoa->id,
            'amount' => 15000000,
        ]);

        $this->assertDatabaseHas('receivables', [
            'invoice_number' => 'AR-001',
            'amount' => 15000000,
            'paid_amount' => 0,
            'status' => 'unpaid',
        ]);

        $this->assertNotNull($receivable->journal_master_id);
    }

    /** @test */
    public function create_receivable_requires_credit_coa()
    {
        $customer = $this->createCustomer();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Akun pendapatan (kredit) harus dipilih.');

        $this->service->createReceivable([
            'business_unit_id' => $this->unit->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'AR-ERR',
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'credit_coa_id' => null,
            'amount' => 1000000,
        ]);
    }

    // ==================== VOID RECEIVABLE TESTS ====================

    /** @test */
    public function can_void_unpaid_receivable()
    {
        $customer = $this->createCustomer();
        $receivable = $this->service->createReceivable([
            'business_unit_id' => $this->unit->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'AR-VOID',
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'credit_coa_id' => $this->pendapatanCoa->id,
            'amount' => 5000000,
        ]);

        $result = $this->service->voidReceivable($receivable);

        $this->assertEquals('void', $result->status);
    }

    /** @test */
    public function cannot_void_receivable_with_payments()
    {
        $customer = $this->createCustomer();
        $receivable = $this->service->createReceivable([
            'business_unit_id' => $this->unit->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'AR-PAID',
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'credit_coa_id' => $this->pendapatanCoa->id,
            'amount' => 5000000,
        ]);

        $this->service->createReceivablePayment($receivable, [
            'payment_date' => now()->format('Y-m-d'),
            'amount' => 1000000,
            'payment_coa_id' => $this->cashCoa->id,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Tidak dapat membatalkan piutang yang sudah ada pembayaran.');

        $this->service->voidReceivable($receivable->fresh());
    }

    // ==================== RECEIVABLE PAYMENT TESTS ====================

    /** @test */
    public function can_make_receivable_partial_payment()
    {
        $customer = $this->createCustomer();
        $receivable = $this->service->createReceivable([
            'business_unit_id' => $this->unit->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'AR-PART',
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'credit_coa_id' => $this->pendapatanCoa->id,
            'amount' => 10000000,
        ]);

        $payment = $this->service->createReceivablePayment($receivable, [
            'payment_date' => now()->format('Y-m-d'),
            'amount' => 4000000,
            'payment_coa_id' => $this->cashCoa->id,
            'reference' => 'RCV-001',
        ]);

        $receivable->refresh();

        $this->assertEquals(4000000, $receivable->paid_amount);
        $this->assertEquals('partial', $receivable->status);
        $this->assertEquals(6000000, $receivable->remaining);
        $this->assertNotNull($payment->journal_master_id);
    }

    /** @test */
    public function can_make_receivable_full_payment()
    {
        $customer = $this->createCustomer();
        $receivable = $this->service->createReceivable([
            'business_unit_id' => $this->unit->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'AR-FULL',
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'credit_coa_id' => $this->pendapatanCoa->id,
            'amount' => 5000000,
        ]);

        $this->service->createReceivablePayment($receivable, [
            'payment_date' => now()->format('Y-m-d'),
            'amount' => 5000000,
            'payment_coa_id' => $this->cashCoa->id,
        ]);

        $receivable->refresh();

        $this->assertEquals('paid', $receivable->status);
        $this->assertEquals(0, $receivable->remaining);
    }

    /** @test */
    public function cannot_receive_more_than_remaining_receivable()
    {
        $customer = $this->createCustomer();
        $receivable = $this->service->createReceivable([
            'business_unit_id' => $this->unit->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'AR-OVER',
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'credit_coa_id' => $this->pendapatanCoa->id,
            'amount' => 5000000,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Jumlah penerimaan melebihi sisa piutang');

        $this->service->createReceivablePayment($receivable, [
            'payment_date' => now()->format('Y-m-d'),
            'amount' => 6000000,
            'payment_coa_id' => $this->cashCoa->id,
        ]);
    }

    /** @test */
    public function cannot_receive_already_paid_receivable()
    {
        $customer = $this->createCustomer();
        $receivable = $this->service->createReceivable([
            'business_unit_id' => $this->unit->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'AR-DPAID',
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'credit_coa_id' => $this->pendapatanCoa->id,
            'amount' => 1000000,
        ]);

        $this->service->createReceivablePayment($receivable, [
            'payment_date' => now()->format('Y-m-d'),
            'amount' => 1000000,
            'payment_coa_id' => $this->cashCoa->id,
        ]);

        $receivable->refresh();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Piutang ini sudah lunas.');

        $this->service->createReceivablePayment($receivable, [
            'payment_date' => now()->format('Y-m-d'),
            'amount' => 500000,
            'payment_coa_id' => $this->cashCoa->id,
        ]);
    }

    // ==================== AGING REPORT TESTS ====================

    /** @test */
    public function payable_aging_buckets_current()
    {
        $vendor = $this->createVendor();

        // Payable due in the future = current
        Payable::withoutEvents(fn() => Payable::create([
            'business_unit_id' => $this->unit->id,
            'vendor_id' => $vendor->id,
            'invoice_number' => 'AG-CUR',
            'invoice_date' => now(),
            'due_date' => now()->addDays(15),
            'input_amount' => 5000000,
            'dpp' => 5000000,
            'pph23_rate' => 0,
            'pph23_amount' => 0,
            'amount_due' => 5000000,
            'paid_amount' => 0,
            'status' => 'unpaid',
        ]));

        $aging = ApArService::getPayableAging();

        $this->assertEquals(5000000, $aging['current']['total']);
        $this->assertCount(1, $aging['current']['items']);
    }

    /** @test */
    public function payable_aging_buckets_overdue()
    {
        $vendor = $this->createVendor();

        // 10 days overdue = 1-30 bucket
        Payable::withoutEvents(fn() => Payable::create([
            'business_unit_id' => $this->unit->id,
            'vendor_id' => $vendor->id,
            'invoice_number' => 'AG-10',
            'invoice_date' => now()->subDays(40),
            'due_date' => now()->subDays(10),
            'input_amount' => 3000000,
            'dpp' => 3000000,
            'pph23_rate' => 0,
            'pph23_amount' => 0,
            'amount_due' => 3000000,
            'paid_amount' => 0,
            'status' => 'unpaid',
        ]));

        // 50 days overdue = 31-60 bucket
        Payable::withoutEvents(fn() => Payable::create([
            'business_unit_id' => $this->unit->id,
            'vendor_id' => $vendor->id,
            'invoice_number' => 'AG-50',
            'invoice_date' => now()->subDays(80),
            'due_date' => now()->subDays(50),
            'input_amount' => 7000000,
            'dpp' => 7000000,
            'pph23_rate' => 0,
            'pph23_amount' => 0,
            'amount_due' => 7000000,
            'paid_amount' => 0,
            'status' => 'unpaid',
        ]));

        $aging = ApArService::getPayableAging();

        $this->assertEquals(3000000, $aging['1_30']['total']);
        $this->assertEquals(7000000, $aging['31_60']['total']);
    }

    /** @test */
    public function payable_aging_filters_by_business_unit()
    {
        $vendor = $this->createVendor();

        $unit2 = BusinessUnit::withoutEvents(fn() => BusinessUnit::create([
            'code' => 'UNT-002', 'name' => 'Unit 2', 'is_active' => true,
        ]));

        Payable::withoutEvents(fn() => Payable::create([
            'business_unit_id' => $this->unit->id,
            'vendor_id' => $vendor->id,
            'invoice_number' => 'AG-U1',
            'invoice_date' => now(),
            'due_date' => now()->addDays(10),
            'input_amount' => 5000000,
            'dpp' => 5000000,
            'pph23_rate' => 0,
            'pph23_amount' => 0,
            'amount_due' => 5000000,
            'paid_amount' => 0,
            'status' => 'unpaid',
        ]));

        Payable::withoutEvents(fn() => Payable::create([
            'business_unit_id' => $unit2->id,
            'vendor_id' => $vendor->id,
            'invoice_number' => 'AG-U2',
            'invoice_date' => now(),
            'due_date' => now()->addDays(10),
            'input_amount' => 8000000,
            'dpp' => 8000000,
            'pph23_rate' => 0,
            'pph23_amount' => 0,
            'amount_due' => 8000000,
            'paid_amount' => 0,
            'status' => 'unpaid',
        ]));

        $aging = ApArService::getPayableAging($this->unit->id);
        $totalAll = collect($aging)->sum('total');
        $this->assertEquals(5000000, $totalAll);
    }

    /** @test */
    public function receivable_aging_works()
    {
        $customer = $this->createCustomer();

        Receivable::withoutEvents(fn() => Receivable::create([
            'business_unit_id' => $this->unit->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'RAG-001',
            'invoice_date' => now(),
            'due_date' => now()->addDays(5),
            'amount' => 10000000,
            'paid_amount' => 0,
            'status' => 'unpaid',
        ]));

        $aging = ApArService::getReceivableAging();

        $this->assertEquals(10000000, $aging['current']['total']);
    }

    // ==================== MODEL TESTS ====================

    /** @test */
    public function payable_remaining_computed_correctly()
    {
        $vendor = $this->createVendor();

        $payable = Payable::withoutEvents(fn() => Payable::create([
            'business_unit_id' => $this->unit->id,
            'vendor_id' => $vendor->id,
            'invoice_number' => 'MDL-001',
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'input_amount' => 10000000,
            'dpp' => 10000000,
            'pph23_rate' => 0,
            'pph23_amount' => 0,
            'amount_due' => 10000000,
            'paid_amount' => 3000000,
            'status' => 'partial',
        ]));

        $this->assertEquals(7000000, $payable->remaining);
    }

    /** @test */
    public function payable_is_overdue_when_past_due_and_not_paid()
    {
        $vendor = $this->createVendor();

        $payable = Payable::withoutEvents(fn() => Payable::create([
            'business_unit_id' => $this->unit->id,
            'vendor_id' => $vendor->id,
            'invoice_number' => 'MDL-OD',
            'invoice_date' => now()->subDays(60),
            'due_date' => now()->subDays(10),
            'input_amount' => 5000000,
            'dpp' => 5000000,
            'pph23_rate' => 0,
            'pph23_amount' => 0,
            'amount_due' => 5000000,
            'paid_amount' => 0,
            'status' => 'unpaid',
        ]));

        $this->assertTrue($payable->isOverdue);
    }

    /** @test */
    public function payable_not_overdue_when_paid()
    {
        $vendor = $this->createVendor();

        $payable = Payable::withoutEvents(fn() => Payable::create([
            'business_unit_id' => $this->unit->id,
            'vendor_id' => $vendor->id,
            'invoice_number' => 'MDL-PAID',
            'invoice_date' => now()->subDays(60),
            'due_date' => now()->subDays(10),
            'input_amount' => 5000000,
            'dpp' => 5000000,
            'pph23_rate' => 0,
            'pph23_amount' => 0,
            'amount_due' => 5000000,
            'paid_amount' => 5000000,
            'status' => 'paid',
        ]));

        $this->assertFalse($payable->isOverdue);
    }

    /** @test */
    public function receivable_remaining_computed_correctly()
    {
        $customer = $this->createCustomer();

        $receivable = Receivable::withoutEvents(fn() => Receivable::create([
            'business_unit_id' => $this->unit->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'RMDL-001',
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'amount' => 10000000,
            'paid_amount' => 4000000,
            'status' => 'partial',
        ]));

        $this->assertEquals(6000000, $receivable->remaining);
    }

    /** @test */
    public function payable_scope_outstanding()
    {
        $vendor = $this->createVendor();

        Payable::withoutEvents(fn() => Payable::create([
            'business_unit_id' => $this->unit->id,
            'vendor_id' => $vendor->id,
            'invoice_number' => 'SC-UNPAID',
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'input_amount' => 5000000,
            'dpp' => 5000000,
            'pph23_rate' => 0,
            'pph23_amount' => 0,
            'amount_due' => 5000000,
            'paid_amount' => 0,
            'status' => 'unpaid',
        ]));

        Payable::withoutEvents(fn() => Payable::create([
            'business_unit_id' => $this->unit->id,
            'vendor_id' => $vendor->id,
            'invoice_number' => 'SC-PAID',
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'input_amount' => 3000000,
            'dpp' => 3000000,
            'pph23_rate' => 0,
            'pph23_amount' => 0,
            'amount_due' => 3000000,
            'paid_amount' => 3000000,
            'status' => 'paid',
        ]));

        Payable::withoutEvents(fn() => Payable::create([
            'business_unit_id' => $this->unit->id,
            'vendor_id' => $vendor->id,
            'invoice_number' => 'SC-VOID',
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'input_amount' => 2000000,
            'dpp' => 2000000,
            'pph23_rate' => 0,
            'pph23_amount' => 0,
            'amount_due' => 2000000,
            'paid_amount' => 0,
            'status' => 'void',
        ]));

        $outstanding = Payable::outstanding()->get();
        $this->assertCount(1, $outstanding);
        $this->assertEquals('SC-UNPAID', $outstanding->first()->invoice_number);
    }
}
