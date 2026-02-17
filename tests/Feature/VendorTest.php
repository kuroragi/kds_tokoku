<?php

namespace Tests\Feature;

use App\Livewire\NameCard\VendorForm;
use App\Livewire\NameCard\VendorList;
use App\Models\BusinessUnit;
use App\Models\Vendor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VendorTest extends TestCase
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

    protected function createVendor(array $overrides = [], ?int $attachToUnit = null): Vendor
    {
        $vendor = Vendor::withoutEvents(function () use ($overrides) {
            return Vendor::create(array_merge([
                'code' => 'VND-001',
                'name' => 'Vendor Test',
                'type' => 'distributor',
                'is_active' => true,
                'is_pph23' => false,
            ], $overrides));
        });

        if ($attachToUnit !== null) {
            $vendor->businessUnits()->attach($attachToUnit);
        }

        return $vendor;
    }

    // ==================== PAGE ACCESS TESTS ====================

    /** @test */
    public function vendor_index_page_is_accessible()
    {
        $response = $this->get(route('vendor.index'));
        $response->assertStatus(200);
        $response->assertSee('Vendor');
    }

    /** @test */
    public function guest_cannot_access_vendor_page()
    {
        auth()->logout();
        $this->get(route('vendor.index'))->assertRedirect(route('login'));
    }

    // ==================== LIST COMPONENT TESTS ====================

    /** @test */
    public function vendor_list_renders_successfully()
    {
        Livewire::test(VendorList::class)->assertStatus(200);
    }

    /** @test */
    public function superadmin_vendor_list_shows_all_vendors()
    {
        $this->createVendor(['code' => 'VND-001', 'name' => 'Vendor A'], $this->unit->id);
        $this->createVendor(['code' => 'VND-002', 'name' => 'Vendor B']);

        Livewire::test(VendorList::class)
            ->assertSee('Vendor A')
            ->assertSee('Vendor B');
    }

    /** @test */
    public function vendor_list_can_search()
    {
        $this->createVendor(['code' => 'VND-001', 'name' => 'Vendor Alpha'], $this->unit->id);
        $this->createVendor(['code' => 'VND-002', 'name' => 'Vendor Beta'], $this->unit->id);

        Livewire::test(VendorList::class)
            ->set('search', 'Alpha')
            ->assertSee('Vendor Alpha')
            ->assertDontSee('Vendor Beta');
    }

    /** @test */
    public function vendor_list_can_filter_by_status()
    {
        $this->createVendor(['code' => 'VND-001', 'name' => 'Active Vendor', 'is_active' => true], $this->unit->id);
        $this->createVendor(['code' => 'VND-002', 'name' => 'Inactive Vendor', 'is_active' => false], $this->unit->id);

        Livewire::test(VendorList::class)
            ->set('filterStatus', '1')
            ->assertSee('Active Vendor')
            ->assertDontSee('Inactive Vendor');
    }

    /** @test */
    public function superadmin_can_filter_vendors_by_unit()
    {
        $unit2 = BusinessUnit::withoutEvents(fn() =>
            BusinessUnit::create(['code' => 'UNT-002', 'name' => 'Unit 2', 'is_active' => true])
        );

        $this->createVendor(['code' => 'VND-001', 'name' => 'Vendor Unit 1'], $this->unit->id);
        $this->createVendor(['code' => 'VND-002', 'name' => 'Vendor Unit 2'], $unit2->id);

        Livewire::test(VendorList::class)
            ->set('filterUnit', $this->unit->id)
            ->assertSee('Vendor Unit 1')
            ->assertDontSee('Vendor Unit 2');
    }

    /** @test */
    public function vendor_list_can_sort()
    {
        $this->createVendor(['code' => 'ZZZ', 'name' => 'Zebra'], $this->unit->id);
        $this->createVendor(['code' => 'AAA', 'name' => 'Alpha'], $this->unit->id);

        Livewire::test(VendorList::class)
            ->call('sortBy', 'name')
            ->assertSeeInOrder(['Alpha', 'Zebra']);
    }

    /** @test */
    public function vendor_list_can_toggle_status()
    {
        $vendor = $this->createVendor([], $this->unit->id);

        Livewire::test(VendorList::class)
            ->call('toggleStatus', $vendor->id)
            ->assertDispatched('alert');

        $this->assertFalse($vendor->fresh()->is_active);
    }

    /** @test */
    public function superadmin_can_delete_vendor_soft_delete()
    {
        $vendor = $this->createVendor([], $this->unit->id);

        Livewire::test(VendorList::class)
            ->call('deleteVendor', $vendor->id)
            ->assertDispatched('alert');

        $this->assertSoftDeleted('vendors', ['id' => $vendor->id]);
    }

    // ==================== FORM COMPONENT TESTS ====================

    /** @test */
    public function vendor_form_renders_successfully()
    {
        Livewire::test(VendorForm::class)->assertStatus(200);
    }

    /** @test */
    public function vendor_form_can_open_modal()
    {
        Livewire::test(VendorForm::class)
            ->call('openVendorModal')
            ->assertSet('showModal', true)
            ->assertSet('isEditing', false);
    }

    /** @test */
    public function vendor_form_can_create_vendor()
    {
        Livewire::test(VendorForm::class)
            ->call('openVendorModal')
            ->set('code', 'VND-NEW')
            ->set('name', 'New Vendor')
            ->set('type', 'distributor')
            ->set('phone', '08123456789')
            ->call('save')
            ->assertDispatched('alert', type: 'success')
            ->assertDispatched('refreshVendorList')
            ->assertSet('showModal', false);

        $this->assertDatabaseHas('vendors', [
            'code' => 'VND-NEW',
            'name' => 'New Vendor',
            'type' => 'distributor',
        ]);
    }

    /** @test */
    public function vendor_form_can_create_vendor_with_pph23()
    {
        Livewire::test(VendorForm::class)
            ->call('openVendorModal')
            ->set('code', 'VND-PPH')
            ->set('name', 'PPh Vendor')
            ->set('type', 'jasa')
            ->set('is_pph23', true)
            ->set('pph23_rate', 2.50)
            ->set('npwp', '12.345.678.9-012.345')
            ->call('save')
            ->assertDispatched('alert', type: 'success');

        $this->assertDatabaseHas('vendors', [
            'code' => 'VND-PPH',
            'is_pph23' => true,
            'npwp' => '12.345.678.9-012.345',
        ]);
    }

    /** @test */
    public function vendor_form_can_create_with_bank_info()
    {
        Livewire::test(VendorForm::class)
            ->call('openVendorModal')
            ->set('code', 'VND-BANK')
            ->set('name', 'Bank Vendor')
            ->set('type', 'supplier_bahan')
            ->set('bank_name', 'BCA')
            ->set('bank_account_number', '1234567890')
            ->set('bank_account_name', 'PT Vendor')
            ->call('save')
            ->assertDispatched('alert', type: 'success');

        $this->assertDatabaseHas('vendors', [
            'code' => 'VND-BANK',
            'bank_name' => 'BCA',
            'bank_account_number' => '1234567890',
        ]);
    }

    /** @test */
    public function vendor_form_can_edit_vendor()
    {
        $vendor = $this->createVendor([], $this->unit->id);

        Livewire::test(VendorForm::class)
            ->call('editVendor', $vendor->id)
            ->assertSet('showModal', true)
            ->assertSet('isEditing', true)
            ->set('name', 'Updated Vendor')
            ->call('save')
            ->assertDispatched('alert', type: 'success');

        $this->assertDatabaseHas('vendors', ['id' => $vendor->id, 'name' => 'Updated Vendor']);
    }

    /** @test */
    public function vendor_form_validates_required_fields()
    {
        Livewire::test(VendorForm::class)
            ->call('openVendorModal')
            ->set('code', '')
            ->set('name', '')
            ->set('type', '')
            ->call('save')
            ->assertHasErrors(['code', 'name', 'type']);
    }

    /** @test */
    public function vendor_form_validates_unique_code()
    {
        $this->createVendor(['code' => 'VND-001']);

        Livewire::test(VendorForm::class)
            ->call('openVendorModal')
            ->set('code', 'VND-001')
            ->set('name', 'Duplicate')
            ->set('type', 'distributor')
            ->call('save')
            ->assertHasErrors(['code']);
    }

    /** @test */
    public function vendor_form_validates_type_enum()
    {
        Livewire::test(VendorForm::class)
            ->call('openVendorModal')
            ->set('code', 'VND-001')
            ->set('name', 'Test')
            ->set('type', 'invalid_type')
            ->call('save')
            ->assertHasErrors(['type']);
    }

    /** @test */
    public function vendor_form_can_close_modal()
    {
        Livewire::test(VendorForm::class)
            ->call('openVendorModal')
            ->assertSet('showModal', true)
            ->call('closeModal')
            ->assertSet('showModal', false);
    }

    // ==================== VENDOR PIVOT / GLOBAL LOGIC TESTS ====================

    /** @test */
    public function vendor_is_global_no_business_unit_column()
    {
        $vendor = $this->createVendor();
        $this->assertNull($vendor->business_unit_id ?? null);
        $this->assertArrayNotHasKey('business_unit_id', $vendor->getAttributes());
    }

    /** @test */
    public function vendor_can_be_attached_to_business_unit()
    {
        $vendor = $this->createVendor();
        $vendor->businessUnits()->attach($this->unit->id);

        $this->assertDatabaseHas('business_unit_vendor', [
            'vendor_id' => $vendor->id,
            'business_unit_id' => $this->unit->id,
        ]);
    }

    /** @test */
    public function vendor_can_be_attached_to_multiple_units()
    {
        $unit2 = BusinessUnit::withoutEvents(fn() =>
            BusinessUnit::create(['code' => 'UNT-002', 'name' => 'Unit 2', 'is_active' => true])
        );

        $vendor = $this->createVendor();
        $vendor->businessUnits()->attach([$this->unit->id, $unit2->id]);

        $this->assertEquals(2, $vendor->businessUnits()->count());
    }

    /** @test */
    public function vendor_by_business_unit_scope_works()
    {
        $unit2 = BusinessUnit::withoutEvents(fn() =>
            BusinessUnit::create(['code' => 'UNT-002', 'name' => 'Unit 2', 'is_active' => true])
        );

        $vendor1 = $this->createVendor(['code' => 'VND-001'], $this->unit->id);
        $vendor2 = $this->createVendor(['code' => 'VND-002'], $unit2->id);

        $result = Vendor::byBusinessUnit($this->unit->id)->get();
        $this->assertTrue($result->contains($vendor1));
        $this->assertFalse($result->contains($vendor2));
    }

    /** @test */
    public function vendor_has_types_constant()
    {
        $types = Vendor::TYPES;
        $this->assertArrayHasKey('distributor', $types);
        $this->assertArrayHasKey('supplier_bahan', $types);
        $this->assertArrayHasKey('jasa', $types);
        $this->assertArrayHasKey('lainnya', $types);
    }

    // ==================== NON-SUPERADMIN BEHAVIOR TESTS ====================

    /** @test */
    public function non_superadmin_only_sees_own_unit_vendors()
    {
        $unit2 = BusinessUnit::withoutEvents(fn() =>
            BusinessUnit::create(['code' => 'UNT-002', 'name' => 'Unit 2', 'is_active' => true])
        );

        $this->createVendor(['code' => 'VND-001', 'name' => 'My Vendor'], $this->unit->id);
        $this->createVendor(['code' => 'VND-002', 'name' => 'Other Vendor'], $unit2->id);
        $this->createVendor(['code' => 'VND-003', 'name' => 'Unattached Vendor']);

        $regularUser = User::withoutEvents(fn() => User::factory()->create(['business_unit_id' => $this->unit->id]));
        $this->actingAs($regularUser);

        Livewire::test(VendorList::class)
            ->assertSee('My Vendor')
            ->assertDontSee('Other Vendor')
            ->assertDontSee('Unattached Vendor');
    }

    /** @test */
    public function non_superadmin_create_auto_attaches_to_own_unit()
    {
        $regularUser = User::withoutEvents(fn() => User::factory()->create(['business_unit_id' => $this->unit->id]));
        $this->actingAs($regularUser);

        Livewire::test(VendorForm::class)
            ->call('openVendorModal')
            ->set('code', 'VND-AUTO')
            ->set('name', 'Auto Attached Vendor')
            ->set('type', 'distributor')
            ->call('save')
            ->assertHasNoErrors();

        $vendor = Vendor::where('code', 'VND-AUTO')->first();
        $this->assertNotNull($vendor);
        $this->assertDatabaseHas('business_unit_vendor', [
            'vendor_id' => $vendor->id,
            'business_unit_id' => $this->unit->id,
        ]);
    }

    /** @test */
    public function non_superadmin_delete_detaches_only()
    {
        $vendor = $this->createVendor(['code' => 'VND-001'], $this->unit->id);

        $regularUser = User::withoutEvents(fn() => User::factory()->create(['business_unit_id' => $this->unit->id]));
        $this->actingAs($regularUser);

        Livewire::test(VendorList::class)
            ->call('deleteVendor', $vendor->id)
            ->assertDispatched('alert');

        // Vendor still exists (not soft-deleted) but detached from unit
        $this->assertDatabaseHas('vendors', ['id' => $vendor->id, 'deleted_at' => null]);
        $this->assertDatabaseMissing('business_unit_vendor', [
            'vendor_id' => $vendor->id,
            'business_unit_id' => $this->unit->id,
        ]);
    }

    /** @test */
    public function superadmin_create_also_attaches_when_unit_available()
    {
        // Superadmin with a business_unit_id should also auto-attach
        $this->user->business_unit_id = $this->unit->id;
        $this->user->save();

        Livewire::test(VendorForm::class)
            ->call('openVendorModal')
            ->set('code', 'VND-SA')
            ->set('name', 'Superadmin Vendor')
            ->set('type', 'jasa')
            ->call('save')
            ->assertHasNoErrors();

        $vendor = Vendor::where('code', 'VND-SA')->first();
        $this->assertNotNull($vendor);
    }

    // ==================== VENDOR ACTIVE SCOPE ====================

    /** @test */
    public function vendor_active_scope_works()
    {
        $this->createVendor(['code' => 'VND-001', 'is_active' => true]);
        $this->createVendor(['code' => 'VND-002', 'is_active' => false]);

        $this->assertEquals(1, Vendor::active()->count());
    }
}
