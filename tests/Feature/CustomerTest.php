<?php

namespace Tests\Feature;

use App\Livewire\NameCard\CustomerForm;
use App\Livewire\NameCard\CustomerList;
use App\Models\BusinessUnit;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected BusinessUnit $unit;

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
    }

    protected function createCustomer(array $overrides = []): Customer
    {
        return Customer::withoutEvents(function () use ($overrides) {
            return Customer::create(array_merge([
                'business_unit_id' => $this->unit->id,
                'code' => 'CUST-001',
                'name' => 'Pelanggan Test',
                'is_active' => true,
            ], $overrides));
        });
    }

    // ==================== PAGE ACCESS TESTS ====================

    /** @test */
    public function customer_index_page_is_accessible()
    {
        $response = $this->get(route('customer.index'));
        $response->assertStatus(200);
        $response->assertSee('Pelanggan');
    }

    /** @test */
    public function guest_cannot_access_customer_page()
    {
        auth()->logout();
        $this->get(route('customer.index'))->assertRedirect(route('login'));
    }

    // ==================== LIST COMPONENT TESTS ====================

    /** @test */
    public function customer_list_renders_successfully()
    {
        Livewire::test(CustomerList::class)->assertStatus(200);
    }

    /** @test */
    public function customer_list_shows_customers()
    {
        $this->createCustomer(['code' => 'CUST-001', 'name' => 'Customer A']);
        $this->createCustomer(['code' => 'CUST-002', 'name' => 'Customer B']);

        Livewire::test(CustomerList::class)
            ->assertSee('Customer A')
            ->assertSee('Customer B');
    }

    /** @test */
    public function customer_list_can_search()
    {
        $this->createCustomer(['code' => 'CUST-001', 'name' => 'Customer A']);
        $this->createCustomer(['code' => 'CUST-002', 'name' => 'Customer B']);

        Livewire::test(CustomerList::class)
            ->set('search', 'Customer A')
            ->assertSee('Customer A')
            ->assertDontSee('Customer B');
    }

    /** @test */
    public function customer_list_can_filter_by_unit()
    {
        $unit2 = BusinessUnit::withoutEvents(fn() =>
            BusinessUnit::create(['code' => 'UNT-002', 'name' => 'Unit 2', 'is_active' => true])
        );

        $this->createCustomer(['code' => 'CUST-001', 'name' => 'Customer Unit 1']);
        Customer::withoutEvents(fn() => Customer::create([
            'business_unit_id' => $unit2->id, 'code' => 'CUST-002', 'name' => 'Customer Unit 2', 'is_active' => true,
        ]));

        Livewire::test(CustomerList::class)
            ->set('filterUnit', $this->unit->id)
            ->assertSee('Customer Unit 1')
            ->assertDontSee('Customer Unit 2');
    }

    /** @test */
    public function customer_list_can_filter_by_status()
    {
        $this->createCustomer(['code' => 'CUST-001', 'name' => 'Active Customer', 'is_active' => true]);
        $this->createCustomer(['code' => 'CUST-002', 'name' => 'Inactive Customer', 'is_active' => false]);

        Livewire::test(CustomerList::class)
            ->set('filterStatus', '1')
            ->assertSee('Active Customer')
            ->assertDontSee('Inactive Customer');
    }

    /** @test */
    public function customer_list_can_sort()
    {
        $this->createCustomer(['code' => 'ZZZ', 'name' => 'Zebra']);
        $this->createCustomer(['code' => 'AAA', 'name' => 'Alpha']);

        Livewire::test(CustomerList::class)
            ->call('sortBy', 'name')
            ->assertSeeInOrder(['Alpha', 'Zebra']);
    }

    /** @test */
    public function customer_list_can_toggle_status()
    {
        $customer = $this->createCustomer();

        Livewire::test(CustomerList::class)
            ->call('toggleStatus', $customer->id)
            ->assertDispatched('alert');

        $this->assertFalse($customer->fresh()->is_active);
    }

    /** @test */
    public function customer_list_can_delete_customer()
    {
        $customer = $this->createCustomer();

        Livewire::test(CustomerList::class)
            ->call('deleteCustomer', $customer->id)
            ->assertDispatched('alert');

        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
    }

    // ==================== FORM COMPONENT TESTS ====================

    /** @test */
    public function customer_form_renders_successfully()
    {
        Livewire::test(CustomerForm::class)->assertStatus(200);
    }

    /** @test */
    public function customer_form_can_open_modal()
    {
        Livewire::test(CustomerForm::class)
            ->call('openCustomerModal')
            ->assertSet('showModal', true)
            ->assertSet('isEditing', false);
    }

    /** @test */
    public function customer_form_can_create_customer()
    {
        Livewire::test(CustomerForm::class)
            ->call('openCustomerModal')
            ->set('business_unit_id', $this->unit->id)
            ->set('code', 'CUST-NEW')
            ->set('name', 'New Customer')
            ->set('phone', '08123456789')
            ->set('city', 'Jakarta')
            ->call('save')
            ->assertDispatched('alert', type: 'success')
            ->assertDispatched('refreshCustomerList')
            ->assertSet('showModal', false);

        $this->assertDatabaseHas('customers', [
            'code' => 'CUST-NEW',
            'name' => 'New Customer',
            'city' => 'Jakarta',
        ]);
    }

    /** @test */
    public function customer_form_can_edit_customer()
    {
        $customer = $this->createCustomer();

        Livewire::test(CustomerForm::class)
            ->call('editCustomer', $customer->id)
            ->assertSet('showModal', true)
            ->assertSet('isEditing', true)
            ->set('name', 'Updated Customer')
            ->call('save')
            ->assertDispatched('alert', type: 'success');

        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'name' => 'Updated Customer']);
    }

    /** @test */
    public function customer_form_validates_required_fields()
    {
        Livewire::test(CustomerForm::class)
            ->call('openCustomerModal')
            ->set('business_unit_id', '')
            ->set('code', '')
            ->set('name', '')
            ->call('save')
            ->assertHasErrors(['business_unit_id', 'code', 'name']);
    }

    /** @test */
    public function customer_form_validates_unique_code_per_unit()
    {
        $this->createCustomer(['code' => 'CUST-001']);

        Livewire::test(CustomerForm::class)
            ->call('openCustomerModal')
            ->set('business_unit_id', $this->unit->id)
            ->set('code', 'CUST-001')
            ->set('name', 'Duplicate')
            ->call('save')
            ->assertHasErrors(['code']);
    }

    /** @test */
    public function customer_form_allows_same_code_in_different_units()
    {
        $this->createCustomer(['code' => 'CUST-001']);

        $unit2 = BusinessUnit::withoutEvents(fn() =>
            BusinessUnit::create(['code' => 'UNT-002', 'name' => 'Unit 2', 'is_active' => true])
        );

        Livewire::test(CustomerForm::class)
            ->call('openCustomerModal')
            ->set('business_unit_id', $unit2->id)
            ->set('code', 'CUST-001')
            ->set('name', 'Same Code Different Unit')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('customers', 2);
    }

    /** @test */
    public function customer_form_can_close_modal()
    {
        Livewire::test(CustomerForm::class)
            ->call('openCustomerModal')
            ->assertSet('showModal', true)
            ->call('closeModal')
            ->assertSet('showModal', false);
    }

    // ==================== MODEL TESTS ====================

    /** @test */
    public function customer_belongs_to_business_unit()
    {
        $customer = $this->createCustomer();
        $this->assertInstanceOf(BusinessUnit::class, $customer->businessUnit);
    }

    /** @test */
    public function customer_active_scope_works()
    {
        $this->createCustomer(['code' => 'CUST-001', 'is_active' => true]);
        $this->createCustomer(['code' => 'CUST-002', 'is_active' => false]);

        $this->assertEquals(1, Customer::active()->where('business_unit_id', $this->unit->id)->count());
    }

    // ==================== BUSINESS UNIT SCOPING TESTS ====================

    /** @test */
    public function non_superadmin_list_only_sees_own_unit_customers()
    {
        $unit2 = BusinessUnit::withoutEvents(fn() => BusinessUnit::create(['code' => 'UNT-002', 'name' => 'Unit 2', 'is_active' => true]));

        $this->createCustomer(['code' => 'CUST-001', 'name' => 'Customer Unit 1']);
        Customer::withoutEvents(fn() => Customer::create([
            'business_unit_id' => $unit2->id, 'code' => 'CUST-002', 'name' => 'Customer Unit 2', 'is_active' => true,
        ]));

        $regularUser = User::withoutEvents(fn() => User::factory()->create(['business_unit_id' => $this->unit->id]));
        $this->actingAs($regularUser);

        Livewire::test(CustomerList::class)
            ->assertSee('Customer Unit 1')
            ->assertDontSee('Customer Unit 2');
    }

    /** @test */
    public function non_superadmin_form_auto_fills_business_unit_id()
    {
        $regularUser = User::withoutEvents(fn() => User::factory()->create(['business_unit_id' => $this->unit->id]));
        $this->actingAs($regularUser);

        Livewire::test(CustomerForm::class)
            ->call('openCustomerModal')
            ->assertSet('business_unit_id', $this->unit->id);
    }

    /** @test */
    public function non_superadmin_save_uses_own_business_unit_id()
    {
        $regularUser = User::withoutEvents(fn() => User::factory()->create(['business_unit_id' => $this->unit->id]));
        $this->actingAs($regularUser);

        Livewire::test(CustomerForm::class)
            ->call('openCustomerModal')
            ->set('code', 'CUST-AUTO')
            ->set('name', 'Auto Customer')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('customers', [
            'code' => 'CUST-AUTO',
            'business_unit_id' => $this->unit->id,
        ]);
    }
}
