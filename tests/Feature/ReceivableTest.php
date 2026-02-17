<?php

namespace Tests\Feature;

use App\Livewire\ApAr\ReceivableForm;
use App\Livewire\ApAr\ReceivableList;
use App\Livewire\ApAr\ReceivablePaymentForm;
use App\Models\BusinessUnit;
use App\Models\BusinessUnitCoaMapping;
use App\Models\COA;
use App\Models\Customer;
use App\Models\Period;
use App\Models\Receivable;
use App\Models\User;
use App\Services\ApArService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReceivableTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected BusinessUnit $unit;
    protected Period $period;
    protected COA $cashCoa;
    protected COA $piutangUsahaCoa;
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

        $this->cashCoa = COA::create([
            'code' => '1101', 'name' => 'Kas', 'type' => 'aktiva',
            'level' => 2, 'order' => 1, 'is_active' => true, 'is_leaf_account' => true,
        ]);

        $this->piutangUsahaCoa = COA::create([
            'code' => '1201', 'name' => 'Piutang Usaha', 'type' => 'aktiva',
            'level' => 2, 'order' => 2, 'is_active' => true, 'is_leaf_account' => true,
        ]);

        $this->pendapatanCoa = COA::create([
            'code' => '4101', 'name' => 'Pendapatan Utama', 'type' => 'pendapatan',
            'level' => 2, 'order' => 1, 'is_active' => true, 'is_leaf_account' => true,
        ]);

        BusinessUnitCoaMapping::create([
            'business_unit_id' => $this->unit->id,
            'account_key' => 'piutang_usaha',
            'label' => 'Piutang Usaha',
            'coa_id' => $this->piutangUsahaCoa->id,
        ]);
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

    protected function createReceivable(array $overrides = []): Receivable
    {
        $customer = $overrides['customer'] ?? $this->createCustomer();
        unset($overrides['customer']);

        return Receivable::withoutEvents(fn() => Receivable::create(array_merge([
            'business_unit_id' => $this->unit->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'AR-001',
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'credit_coa_id' => $this->pendapatanCoa->id,
            'amount' => 10000000,
            'paid_amount' => 0,
            'status' => 'unpaid',
        ], $overrides)));
    }

    // ==================== PAGE ACCESS TESTS ====================

    /** @test */
    public function receivable_page_is_accessible()
    {
        $response = $this->get(route('receivable.index'));
        $response->assertStatus(200);
        $response->assertSee('Piutang Usaha');
    }

    /** @test */
    public function guest_cannot_access_receivable_page()
    {
        auth()->logout();
        $this->get(route('receivable.index'))->assertRedirect(route('login'));
    }

    // ==================== REPORT PAGE ACCESS TESTS ====================

    /** @test */
    public function aging_report_page_is_accessible()
    {
        $response = $this->get(route('apar-report.aging'));
        $response->assertStatus(200);
        $response->assertSee('Aging');
    }

    /** @test */
    public function outstanding_report_page_is_accessible()
    {
        $response = $this->get(route('apar-report.outstanding'));
        $response->assertStatus(200);
        $response->assertSee('Outstanding');
    }

    /** @test */
    public function payment_history_report_page_is_accessible()
    {
        $response = $this->get(route('apar-report.payment-history'));
        $response->assertStatus(200);
        $response->assertSee('Riwayat');
    }

    // ==================== LIST COMPONENT TESTS ====================

    /** @test */
    public function receivable_list_renders_successfully()
    {
        Livewire::test(ReceivableList::class)->assertStatus(200);
    }

    /** @test */
    public function receivable_list_shows_receivables()
    {
        $this->createReceivable(['invoice_number' => 'AR-SHOW']);

        Livewire::test(ReceivableList::class)
            ->assertSee('AR-SHOW');
    }

    /** @test */
    public function receivable_list_can_search()
    {
        $cust1 = $this->createCustomer(['code' => 'CST-001', 'name' => 'Customer Alpha']);
        $cust2 = $this->createCustomer(['code' => 'CST-002', 'name' => 'Customer Beta']);

        $this->createReceivable(['invoice_number' => 'AR-AAA', 'customer' => $cust1]);
        $this->createReceivable(['invoice_number' => 'AR-BBB', 'customer' => $cust2]);

        Livewire::test(ReceivableList::class)
            ->set('search', 'AR-AAA')
            ->assertSee('AR-AAA')
            ->assertDontSee('AR-BBB');
    }

    /** @test */
    public function receivable_list_can_filter_by_status()
    {
        $customer = $this->createCustomer();
        $this->createReceivable([
            'invoice_number' => 'AR-UNPAID',
            'status' => 'unpaid',
            'customer' => $customer,
        ]);
        $this->createReceivable([
            'invoice_number' => 'AR-PAID',
            'status' => 'paid',
            'paid_amount' => 10000000,
            'customer' => $customer,
        ]);

        Livewire::test(ReceivableList::class)
            ->set('filterStatus', 'unpaid')
            ->assertSee('AR-UNPAID')
            ->assertDontSee('AR-PAID');
    }

    /** @test */
    public function receivable_list_can_sort()
    {
        $customer = $this->createCustomer();
        $this->createReceivable(['invoice_number' => 'ZZZ', 'customer' => $customer]);
        $this->createReceivable(['invoice_number' => 'AAA', 'customer' => $customer]);

        Livewire::test(ReceivableList::class)
            ->call('sortBy', 'invoice_number')
            ->assertSeeInOrder(['AAA', 'ZZZ']);
    }

    /** @test */
    public function receivable_list_can_void_receivable()
    {
        $receivable = $this->createReceivable(['invoice_number' => 'AR-VOID']);

        Livewire::test(ReceivableList::class)
            ->call('voidReceivable', $receivable->id)
            ->assertDispatched('alert');

        $this->assertEquals('void', $receivable->fresh()->status);
    }

    /** @test */
    public function receivable_list_can_delete_receivable()
    {
        $receivable = $this->createReceivable(['invoice_number' => 'AR-DEL']);

        Livewire::test(ReceivableList::class)
            ->call('deleteReceivable', $receivable->id)
            ->assertDispatched('alert');

        $this->assertSoftDeleted('receivables', ['id' => $receivable->id]);
    }

    /** @test */
    public function receivable_list_cannot_delete_with_payments()
    {
        $customer = $this->createCustomer();
        $service = app(ApArService::class);

        $receivable = $service->createReceivable([
            'business_unit_id' => $this->unit->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'AR-HAS-PAY',
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'credit_coa_id' => $this->pendapatanCoa->id,
            'amount' => 10000000,
        ]);

        $service->createReceivablePayment($receivable, [
            'payment_date' => now()->format('Y-m-d'),
            'amount' => 1000000,
            'payment_coa_id' => $this->cashCoa->id,
        ]);

        Livewire::test(ReceivableList::class)
            ->call('deleteReceivable', $receivable->id)
            ->assertDispatched('alert', type: 'error');

        $this->assertDatabaseHas('receivables', ['id' => $receivable->id, 'deleted_at' => null]);
    }

    // ==================== FORM COMPONENT TESTS ====================

    /** @test */
    public function receivable_form_renders_successfully()
    {
        Livewire::test(ReceivableForm::class)->assertStatus(200);
    }

    /** @test */
    public function receivable_form_can_open_modal()
    {
        Livewire::test(ReceivableForm::class)
            ->call('openReceivableModal')
            ->assertSet('showModal', true)
            ->assertSet('isEditing', false);
    }

    /** @test */
    public function receivable_form_can_create_receivable()
    {
        $customer = $this->createCustomer();

        Livewire::test(ReceivableForm::class)
            ->call('openReceivableModal')
            ->set('business_unit_id', $this->unit->id)
            ->set('customer_id', $customer->id)
            ->set('invoice_number', 'AR-NEW')
            ->set('invoice_date', now()->format('Y-m-d'))
            ->set('due_date', now()->addDays(30)->format('Y-m-d'))
            ->set('credit_coa_id', $this->pendapatanCoa->id)
            ->set('amount', 15000000)
            ->set('description', 'Test piutang')
            ->call('save')
            ->assertDispatched('alert', type: 'success')
            ->assertDispatched('refreshReceivableList')
            ->assertSet('showModal', false);

        $this->assertDatabaseHas('receivables', [
            'invoice_number' => 'AR-NEW',
            'amount' => 15000000,
        ]);
    }

    /** @test */
    public function receivable_form_validates_required_fields()
    {
        Livewire::test(ReceivableForm::class)
            ->call('openReceivableModal')
            ->set('business_unit_id', '')
            ->set('customer_id', '')
            ->set('invoice_number', '')
            ->set('credit_coa_id', '')
            ->set('amount', '')
            ->call('save')
            ->assertHasErrors(['business_unit_id', 'customer_id', 'invoice_number', 'credit_coa_id', 'amount']);
    }

    /** @test */
    public function receivable_form_validates_unique_invoice_per_unit()
    {
        $customer = $this->createCustomer();
        $this->createReceivable(['invoice_number' => 'AR-DUP', 'customer' => $customer]);

        Livewire::test(ReceivableForm::class)
            ->call('openReceivableModal')
            ->set('business_unit_id', $this->unit->id)
            ->set('customer_id', $customer->id)
            ->set('invoice_number', 'AR-DUP')
            ->set('invoice_date', now()->format('Y-m-d'))
            ->set('due_date', now()->addDays(30)->format('Y-m-d'))
            ->set('credit_coa_id', $this->pendapatanCoa->id)
            ->set('amount', 10000000)
            ->call('save')
            ->assertHasErrors(['invoice_number']);
    }

    /** @test */
    public function receivable_form_can_edit_unpaid_receivable()
    {
        $customer = $this->createCustomer();
        $receivable = $this->createReceivable([
            'invoice_number' => 'AR-EDIT',
            'customer' => $customer,
        ]);

        Livewire::test(ReceivableForm::class)
            ->call('editReceivable', $receivable->id)
            ->assertSet('showModal', true)
            ->assertSet('isEditing', true)
            ->assertSet('invoice_number', 'AR-EDIT');
    }

    /** @test */
    public function receivable_form_cannot_edit_paid_receivable()
    {
        $customer = $this->createCustomer();
        $receivable = $this->createReceivable([
            'invoice_number' => 'AR-NOEDIT',
            'customer' => $customer,
            'status' => 'paid',
            'paid_amount' => 10000000,
        ]);

        Livewire::test(ReceivableForm::class)
            ->call('editReceivable', $receivable->id)
            ->assertDispatched('alert', type: 'error')
            ->assertSet('showModal', false);
    }

    // ==================== PAYMENT FORM TESTS ====================

    /** @test */
    public function receivable_payment_form_renders_successfully()
    {
        Livewire::test(ReceivablePaymentForm::class)->assertStatus(200);
    }

    /** @test */
    public function receivable_payment_form_can_open_for_receivable()
    {
        $customer = $this->createCustomer();
        $service = app(ApArService::class);
        $receivable = $service->createReceivable([
            'business_unit_id' => $this->unit->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'AR-PAY-OPEN',
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'credit_coa_id' => $this->pendapatanCoa->id,
            'amount' => 10000000,
        ]);

        Livewire::test(ReceivablePaymentForm::class)
            ->call('openReceivablePaymentModal', $receivable->id)
            ->assertSet('showModal', true)
            ->assertSet('receivableId', $receivable->id);
    }
}
