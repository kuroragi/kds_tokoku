<?php

namespace Tests\Feature;

use App\Livewire\NameCard\PartnerForm;
use App\Livewire\NameCard\PartnerList;
use App\Models\BusinessUnit;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PartnerTest extends TestCase
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

    protected function createPartner(array $overrides = []): Partner
    {
        return Partner::withoutEvents(function () use ($overrides) {
            return Partner::create(array_merge([
                'business_unit_id' => $this->unit->id,
                'code' => 'PTR-001',
                'name' => 'Partner Test',
                'is_active' => true,
            ], $overrides));
        });
    }

    // ==================== PAGE ACCESS TESTS ====================

    /** @test */
    public function partner_index_page_is_accessible()
    {
        $response = $this->get(route('partner.index'));
        $response->assertStatus(200);
        $response->assertSee('Partner');
    }

    /** @test */
    public function guest_cannot_access_partner_page()
    {
        auth()->logout();
        $this->get(route('partner.index'))->assertRedirect(route('login'));
    }

    // ==================== LIST COMPONENT TESTS ====================

    /** @test */
    public function partner_list_renders_successfully()
    {
        Livewire::test(PartnerList::class)->assertStatus(200);
    }

    /** @test */
    public function partner_list_shows_partners()
    {
        $this->createPartner(['code' => 'PTR-001', 'name' => 'Partner A']);
        $this->createPartner(['code' => 'PTR-002', 'name' => 'Partner B']);

        Livewire::test(PartnerList::class)
            ->assertSee('Partner A')
            ->assertSee('Partner B');
    }

    /** @test */
    public function partner_list_can_search()
    {
        $this->createPartner(['code' => 'PTR-001', 'name' => 'Partner Alpha']);
        $this->createPartner(['code' => 'PTR-002', 'name' => 'Partner Beta']);

        Livewire::test(PartnerList::class)
            ->set('search', 'Alpha')
            ->assertSee('Partner Alpha')
            ->assertDontSee('Partner Beta');
    }

    /** @test */
    public function partner_list_can_filter_by_unit()
    {
        $unit2 = BusinessUnit::withoutEvents(fn() =>
            BusinessUnit::create(['code' => 'UNT-002', 'name' => 'Unit 2', 'is_active' => true])
        );

        $this->createPartner(['code' => 'PTR-001', 'name' => 'Partner Unit 1']);
        Partner::withoutEvents(fn() => Partner::create([
            'business_unit_id' => $unit2->id, 'code' => 'PTR-002', 'name' => 'Partner Unit 2', 'is_active' => true,
        ]));

        Livewire::test(PartnerList::class)
            ->set('filterUnit', $this->unit->id)
            ->assertSee('Partner Unit 1')
            ->assertDontSee('Partner Unit 2');
    }

    /** @test */
    public function partner_list_can_filter_by_status()
    {
        $this->createPartner(['code' => 'PTR-001', 'name' => 'Active Partner', 'is_active' => true]);
        $this->createPartner(['code' => 'PTR-002', 'name' => 'Inactive Partner', 'is_active' => false]);

        Livewire::test(PartnerList::class)
            ->set('filterStatus', '1')
            ->assertSee('Active Partner')
            ->assertDontSee('Inactive Partner');
    }

    /** @test */
    public function partner_list_can_sort()
    {
        $this->createPartner(['code' => 'ZZZ', 'name' => 'Zebra']);
        $this->createPartner(['code' => 'AAA', 'name' => 'Alpha']);

        Livewire::test(PartnerList::class)
            ->call('sortBy', 'name')
            ->assertSeeInOrder(['Alpha', 'Zebra']);
    }

    /** @test */
    public function partner_list_can_toggle_status()
    {
        $partner = $this->createPartner();

        Livewire::test(PartnerList::class)
            ->call('toggleStatus', $partner->id)
            ->assertDispatched('alert');

        $this->assertFalse($partner->fresh()->is_active);
    }

    /** @test */
    public function partner_list_can_delete_partner()
    {
        $partner = $this->createPartner();

        Livewire::test(PartnerList::class)
            ->call('deletePartner', $partner->id)
            ->assertDispatched('alert');

        $this->assertSoftDeleted('partners', ['id' => $partner->id]);
    }

    // ==================== FORM COMPONENT TESTS ====================

    /** @test */
    public function partner_form_renders_successfully()
    {
        Livewire::test(PartnerForm::class)->assertStatus(200);
    }

    /** @test */
    public function partner_form_can_open_modal()
    {
        Livewire::test(PartnerForm::class)
            ->call('openPartnerModal')
            ->assertSet('showModal', true)
            ->assertSet('isEditing', false);
    }

    /** @test */
    public function partner_form_can_create_partner()
    {
        Livewire::test(PartnerForm::class)
            ->call('openPartnerModal')
            ->set('business_unit_id', $this->unit->id)
            ->set('code', 'PTR-NEW')
            ->set('name', 'New Partner')
            ->set('type', 'Mitra Usaha')
            ->set('phone', '08123456789')
            ->call('save')
            ->assertDispatched('alert', type: 'success')
            ->assertDispatched('refreshPartnerList')
            ->assertSet('showModal', false);

        $this->assertDatabaseHas('partners', [
            'code' => 'PTR-NEW',
            'name' => 'New Partner',
            'type' => 'Mitra Usaha',
        ]);
    }

    /** @test */
    public function partner_form_can_create_without_type()
    {
        Livewire::test(PartnerForm::class)
            ->call('openPartnerModal')
            ->set('business_unit_id', $this->unit->id)
            ->set('code', 'PTR-NOTYPE')
            ->set('name', 'No Type')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('partners', [
            'code' => 'PTR-NOTYPE',
            'type' => null,
        ]);
    }

    /** @test */
    public function partner_form_can_edit_partner()
    {
        $partner = $this->createPartner();

        Livewire::test(PartnerForm::class)
            ->call('editPartner', $partner->id)
            ->assertSet('showModal', true)
            ->assertSet('isEditing', true)
            ->set('name', 'Updated Partner')
            ->call('save')
            ->assertDispatched('alert', type: 'success');

        $this->assertDatabaseHas('partners', ['id' => $partner->id, 'name' => 'Updated Partner']);
    }

    /** @test */
    public function partner_form_validates_required_fields()
    {
        Livewire::test(PartnerForm::class)
            ->call('openPartnerModal')
            ->set('business_unit_id', '')
            ->set('code', '')
            ->set('name', '')
            ->call('save')
            ->assertHasErrors(['business_unit_id', 'code', 'name']);
    }

    /** @test */
    public function partner_form_validates_unique_code_per_unit()
    {
        $this->createPartner(['code' => 'PTR-001']);

        Livewire::test(PartnerForm::class)
            ->call('openPartnerModal')
            ->set('business_unit_id', $this->unit->id)
            ->set('code', 'PTR-001')
            ->set('name', 'Duplicate')
            ->call('save')
            ->assertHasErrors(['code']);
    }

    /** @test */
    public function partner_form_allows_same_code_in_different_units()
    {
        $this->createPartner(['code' => 'PTR-001']);

        $unit2 = BusinessUnit::withoutEvents(fn() =>
            BusinessUnit::create(['code' => 'UNT-002', 'name' => 'Unit 2', 'is_active' => true])
        );

        Livewire::test(PartnerForm::class)
            ->call('openPartnerModal')
            ->set('business_unit_id', $unit2->id)
            ->set('code', 'PTR-001')
            ->set('name', 'Same Code Different Unit')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('partners', 2);
    }

    /** @test */
    public function partner_form_can_close_modal()
    {
        Livewire::test(PartnerForm::class)
            ->call('openPartnerModal')
            ->assertSet('showModal', true)
            ->call('closeModal')
            ->assertSet('showModal', false);
    }

    // ==================== MODEL TESTS ====================

    /** @test */
    public function partner_belongs_to_business_unit()
    {
        $partner = $this->createPartner();
        $this->assertInstanceOf(BusinessUnit::class, $partner->businessUnit);
    }

    /** @test */
    public function partner_active_scope_works()
    {
        $this->createPartner(['code' => 'PTR-001', 'is_active' => true]);
        $this->createPartner(['code' => 'PTR-002', 'is_active' => false]);

        $this->assertEquals(1, Partner::active()->where('business_unit_id', $this->unit->id)->count());
    }

    // ==================== BUSINESS UNIT SCOPING TESTS ====================

    /** @test */
    public function non_superadmin_list_only_sees_own_unit_partners()
    {
        $unit2 = BusinessUnit::withoutEvents(fn() => BusinessUnit::create(['code' => 'UNT-002', 'name' => 'Unit 2', 'is_active' => true]));

        $this->createPartner(['code' => 'PTR-001', 'name' => 'Partner Unit 1']);
        Partner::withoutEvents(fn() => Partner::create([
            'business_unit_id' => $unit2->id, 'code' => 'PTR-002', 'name' => 'Partner Unit 2', 'is_active' => true,
        ]));

        $regularUser = User::withoutEvents(fn() => User::factory()->create(['business_unit_id' => $this->unit->id]));
        $this->actingAs($regularUser);

        Livewire::test(PartnerList::class)
            ->assertSee('Partner Unit 1')
            ->assertDontSee('Partner Unit 2');
    }

    /** @test */
    public function non_superadmin_form_auto_fills_business_unit_id()
    {
        $regularUser = User::withoutEvents(fn() => User::factory()->create(['business_unit_id' => $this->unit->id]));
        $this->actingAs($regularUser);

        Livewire::test(PartnerForm::class)
            ->call('openPartnerModal')
            ->assertSet('business_unit_id', $this->unit->id);
    }

    /** @test */
    public function non_superadmin_save_uses_own_business_unit_id()
    {
        $regularUser = User::withoutEvents(fn() => User::factory()->create(['business_unit_id' => $this->unit->id]));
        $this->actingAs($regularUser);

        Livewire::test(PartnerForm::class)
            ->call('openPartnerModal')
            ->set('code', 'PTR-AUTO')
            ->set('name', 'Auto Partner')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('partners', [
            'code' => 'PTR-AUTO',
            'business_unit_id' => $this->unit->id,
        ]);
    }
}
