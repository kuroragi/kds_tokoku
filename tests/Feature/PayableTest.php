<?php

namespace Tests\Feature;

use App\Livewire\ApAr\PayableForm;
use App\Livewire\ApAr\PayableList;
use App\Livewire\ApAr\PayablePaymentForm;
use App\Models\BusinessUnit;
use App\Models\BusinessUnitCoaMapping;
use App\Models\COA;
use App\Models\Payable;
use App\Models\Period;
use App\Models\User;
use App\Models\Vendor;
use App\Services\ApArService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PayableTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected BusinessUnit $unit;
    protected Period $period;
    protected COA $cashCoa;
    protected COA $hutangUsahaCoa;
    protected COA $hutangPajakCoa;
    protected COA $bebanCoa;

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

        $this->hutangUsahaCoa = COA::create([
            'code' => '2101', 'name' => 'Hutang Usaha', 'type' => 'pasiva',
            'level' => 2, 'order' => 1, 'is_active' => true, 'is_leaf_account' => true,
        ]);

        $this->hutangPajakCoa = COA::create([
            'code' => '2102', 'name' => 'Hutang Pajak', 'type' => 'pasiva',
            'level' => 2, 'order' => 2, 'is_active' => true, 'is_leaf_account' => true,
        ]);

        $this->bebanCoa = COA::create([
            'code' => '5101', 'name' => 'Beban Jasa', 'type' => 'beban',
            'level' => 2, 'order' => 1, 'is_active' => true, 'is_leaf_account' => true,
        ]);

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

    protected function createPayable(array $overrides = []): Payable
    {
        $vendor = $overrides['vendor'] ?? $this->createVendor();
        unset($overrides['vendor']);

        return Payable::withoutEvents(fn() => Payable::create(array_merge([
            'business_unit_id' => $this->unit->id,
            'vendor_id' => $vendor->id,
            'invoice_number' => 'INV-001',
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'debit_coa_id' => $this->bebanCoa->id,
            'input_amount' => 5000000,
            'is_net_basis' => false,
            'dpp' => 5000000,
            'pph23_rate' => 0,
            'pph23_amount' => 0,
            'amount_due' => 5000000,
            'paid_amount' => 0,
            'status' => 'unpaid',
        ], $overrides)));
    }

    // ==================== PAGE ACCESS TESTS ====================

    /** @test */
    public function payable_page_is_accessible()
    {
        $response = $this->get(route('payable.index'));
        $response->assertStatus(200);
        $response->assertSee('Hutang Usaha');
    }

    /** @test */
    public function guest_cannot_access_payable_page()
    {
        auth()->logout();
        $this->get(route('payable.index'))->assertRedirect(route('login'));
    }

    // ==================== LIST COMPONENT TESTS ====================

    /** @test */
    public function payable_list_renders_successfully()
    {
        Livewire::test(PayableList::class)->assertStatus(200);
    }

    /** @test */
    public function payable_list_shows_payables()
    {
        $this->createPayable(['invoice_number' => 'INV-SHOW']);

        Livewire::test(PayableList::class)
            ->assertSee('INV-SHOW');
    }

    /** @test */
    public function payable_list_can_search()
    {
        $vendor1 = $this->createVendor(['code' => 'VND-001', 'name' => 'Vendor Alpha']);
        $vendor2 = $this->createVendor(['code' => 'VND-002', 'name' => 'Vendor Beta']);

        $this->createPayable(['invoice_number' => 'INV-AAA', 'vendor' => $vendor1]);
        $this->createPayable(['invoice_number' => 'INV-BBB', 'vendor' => $vendor2]);

        Livewire::test(PayableList::class)
            ->set('search', 'INV-AAA')
            ->assertSee('INV-AAA')
            ->assertDontSee('INV-BBB');
    }

    /** @test */
    public function payable_list_can_filter_by_status()
    {
        $vendor = $this->createVendor();
        $this->createPayable(['invoice_number' => 'INV-UNPAID-X', 'status' => 'unpaid', 'vendor' => $vendor]);
        $this->createPayable(['invoice_number' => 'INV-LUNAS-Y', 'status' => 'paid', 'paid_amount' => 5000000, 'vendor' => $vendor]);

        Livewire::test(PayableList::class)
            ->set('filterStatus', 'unpaid')
            ->assertSee('INV-UNPAID-X')
            ->assertDontSee('INV-LUNAS-Y');
    }

    /** @test */
    public function payable_list_can_sort()
    {
        $vendor = $this->createVendor();
        $this->createPayable(['invoice_number' => 'ZZZ', 'vendor' => $vendor]);
        $this->createPayable(['invoice_number' => 'AAA', 'vendor' => $vendor]);

        Livewire::test(PayableList::class)
            ->call('sortBy', 'invoice_number')
            ->assertSeeInOrder(['AAA', 'ZZZ']);
    }

    /** @test */
    public function payable_list_can_void_payable()
    {
        $payable = $this->createPayable(['invoice_number' => 'INV-VOID']);

        Livewire::test(PayableList::class)
            ->call('voidPayable', $payable->id)
            ->assertDispatched('alert');

        $this->assertEquals('void', $payable->fresh()->status);
    }

    /** @test */
    public function payable_list_can_delete_payable()
    {
        $payable = $this->createPayable(['invoice_number' => 'INV-DEL']);

        Livewire::test(PayableList::class)
            ->call('deletePayable', $payable->id)
            ->assertDispatched('alert');

        $this->assertSoftDeleted('payables', ['id' => $payable->id]);
    }

    /** @test */
    public function payable_list_cannot_delete_with_payments()
    {
        $vendor = $this->createVendor();
        $service = app(ApArService::class);

        $payable = $service->createPayable([
            'business_unit_id' => $this->unit->id,
            'vendor_id' => $vendor->id,
            'invoice_number' => 'INV-HAS-PAY',
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'debit_coa_id' => $this->bebanCoa->id,
            'input_amount' => 5000000,
        ]);

        $service->createPayablePayment($payable, [
            'payment_date' => now()->format('Y-m-d'),
            'amount' => 1000000,
            'payment_coa_id' => $this->cashCoa->id,
        ]);

        Livewire::test(PayableList::class)
            ->call('deletePayable', $payable->id)
            ->assertDispatched('alert', type: 'error');

        $this->assertDatabaseHas('payables', ['id' => $payable->id, 'deleted_at' => null]);
    }

    // ==================== FORM COMPONENT TESTS ====================

    /** @test */
    public function payable_form_renders_successfully()
    {
        Livewire::test(PayableForm::class)->assertStatus(200);
    }

    /** @test */
    public function payable_form_can_open_modal()
    {
        Livewire::test(PayableForm::class)
            ->call('openPayableModal')
            ->assertSet('showModal', true)
            ->assertSet('isEditing', false);
    }

    /** @test */
    public function payable_form_can_create_payable()
    {
        $vendor = $this->createVendor();

        Livewire::test(PayableForm::class)
            ->call('openPayableModal')
            ->set('business_unit_id', $this->unit->id)
            ->set('vendor_id', $vendor->id)
            ->set('invoice_number', 'INV-NEW')
            ->set('invoice_date', now()->format('Y-m-d'))
            ->set('due_date', now()->addDays(30)->format('Y-m-d'))
            ->set('debit_coa_id', $this->bebanCoa->id)
            ->set('input_amount', 5000000)
            ->set('description', 'Test hutang')
            ->call('save')
            ->assertDispatched('alert', type: 'success')
            ->assertDispatched('refreshPayableList')
            ->assertSet('showModal', false);

        $this->assertDatabaseHas('payables', [
            'invoice_number' => 'INV-NEW',
            'input_amount' => 5000000,
        ]);
    }

    /** @test */
    public function payable_form_validates_required_fields()
    {
        Livewire::test(PayableForm::class)
            ->call('openPayableModal')
            ->set('business_unit_id', '')
            ->set('vendor_id', '')
            ->set('invoice_number', '')
            ->set('debit_coa_id', '')
            ->set('input_amount', '')
            ->call('save')
            ->assertHasErrors(['business_unit_id', 'vendor_id', 'invoice_number', 'debit_coa_id', 'input_amount']);
    }

    /** @test */
    public function payable_form_validates_unique_invoice_per_unit()
    {
        $vendor = $this->createVendor();
        $this->createPayable(['invoice_number' => 'INV-DUP', 'vendor' => $vendor]);

        Livewire::test(PayableForm::class)
            ->call('openPayableModal')
            ->set('business_unit_id', $this->unit->id)
            ->set('vendor_id', $vendor->id)
            ->set('invoice_number', 'INV-DUP')
            ->set('invoice_date', now()->format('Y-m-d'))
            ->set('due_date', now()->addDays(30)->format('Y-m-d'))
            ->set('debit_coa_id', $this->bebanCoa->id)
            ->set('input_amount', 5000000)
            ->call('save')
            ->assertHasErrors(['invoice_number']);
    }

    /** @test */
    public function payable_form_detects_vendor_pph23()
    {
        $vendor = $this->createVendor([
            'is_pph23' => true,
            'pph23_rate' => 2.00,
            'is_net_pph23' => true,
        ]);

        Livewire::test(PayableForm::class)
            ->call('openPayableModal')
            ->set('vendor_id', $vendor->id)
            ->assertSet('vendor_is_pph23', true)
            ->assertSet('vendor_pph23_rate', 2.00)
            ->assertSet('is_net_basis', true);
    }

    /** @test */
    public function payable_form_calculates_pph23_realtime()
    {
        $vendor = $this->createVendor([
            'is_pph23' => true,
            'pph23_rate' => 2.00,
            'is_net_pph23' => false,
        ]);

        Livewire::test(PayableForm::class)
            ->call('openPayableModal')
            ->set('vendor_id', $vendor->id)
            ->set('input_amount', 10000000)
            ->assertSet('calc_dpp', 10000000)
            ->assertSet('calc_pph23', 200000)
            ->assertSet('calc_amount_due', 9800000);
    }

    /** @test */
    public function payable_form_can_edit_unpaid_payable()
    {
        $vendor = $this->createVendor();
        $payable = $this->createPayable(['invoice_number' => 'INV-EDIT', 'vendor' => $vendor]);

        Livewire::test(PayableForm::class)
            ->call('editPayable', $payable->id)
            ->assertSet('showModal', true)
            ->assertSet('isEditing', true)
            ->assertSet('invoice_number', 'INV-EDIT');
    }

    /** @test */
    public function payable_form_cannot_edit_paid_payable()
    {
        $vendor = $this->createVendor();
        $payable = $this->createPayable([
            'invoice_number' => 'INV-NOEDIT',
            'vendor' => $vendor,
            'status' => 'paid',
            'paid_amount' => 5000000,
        ]);

        Livewire::test(PayableForm::class)
            ->call('editPayable', $payable->id)
            ->assertDispatched('alert', type: 'error')
            ->assertSet('showModal', false);
    }

    // ==================== PAYMENT FORM TESTS ====================

    /** @test */
    public function payable_payment_form_renders_successfully()
    {
        Livewire::test(PayablePaymentForm::class)->assertStatus(200);
    }

    /** @test */
    public function payable_payment_form_can_open_for_payable()
    {
        $vendor = $this->createVendor();
        $service = app(ApArService::class);
        $payable = $service->createPayable([
            'business_unit_id' => $this->unit->id,
            'vendor_id' => $vendor->id,
            'invoice_number' => 'INV-PAY-OPEN',
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'debit_coa_id' => $this->bebanCoa->id,
            'input_amount' => 5000000,
        ]);

        Livewire::test(PayablePaymentForm::class)
            ->call('openPayablePaymentModal', $payable->id)
            ->assertSet('showModal', true)
            ->assertSet('payableId', $payable->id);
    }
}
