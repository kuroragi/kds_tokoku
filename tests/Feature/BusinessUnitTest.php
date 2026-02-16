<?php

namespace Tests\Feature;

use App\Livewire\BusinessUnit\BusinessUnitForm;
use App\Livewire\BusinessUnit\BusinessUnitList;
use App\Models\BusinessUnit;
use App\Models\BusinessUnitCoaMapping;
use App\Models\COA;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BusinessUnitTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::withoutEvents(function () {
            return User::factory()->create();
        });
        $this->actingAs($this->user);
    }

    // ==================== PAGE ACCESS TESTS ====================

    /** @test */
    public function business_unit_index_page_is_accessible()
    {
        $response = $this->get(route('business-unit.index'));
        $response->assertStatus(200);
        $response->assertSee('Unit Usaha');
    }

    /** @test */
    public function business_unit_create_page_is_accessible()
    {
        $response = $this->get(route('business-unit.create'));
        $response->assertStatus(200);
        $response->assertSee('Tambah Unit Usaha');
    }

    /** @test */
    public function business_unit_edit_page_is_accessible()
    {
        $unit = BusinessUnit::withoutEvents(function () {
            return BusinessUnit::create([
                'code' => 'UNT-001',
                'name' => 'Test Unit',
                'is_active' => true,
            ]);
        });

        $response = $this->get(route('business-unit.edit', $unit));
        $response->assertStatus(200);
        $response->assertSee('Edit Unit Usaha');
    }

    /** @test */
    public function guest_cannot_access_business_unit_pages()
    {
        auth()->logout();

        $this->get(route('business-unit.index'))->assertRedirect(route('login'));
        $this->get(route('business-unit.create'))->assertRedirect(route('login'));
    }

    // ==================== BUSINESS UNIT LIST TESTS ====================

    /** @test */
    public function business_unit_list_renders_successfully()
    {
        Livewire::test(BusinessUnitList::class)
            ->assertStatus(200);
    }

    /** @test */
    public function business_unit_list_shows_units()
    {
        BusinessUnit::withoutEvents(function () {
            BusinessUnit::create(['code' => 'UNT-001', 'name' => 'Toko Alpha', 'is_active' => true]);
            BusinessUnit::create(['code' => 'UNT-002', 'name' => 'Toko Beta', 'is_active' => true]);
        });

        Livewire::test(BusinessUnitList::class)
            ->assertSee('Toko Alpha')
            ->assertSee('Toko Beta')
            ->assertSee('UNT-001')
            ->assertSee('UNT-002');
    }

    /** @test */
    public function business_unit_list_can_search()
    {
        BusinessUnit::withoutEvents(function () {
            BusinessUnit::create(['code' => 'UNT-001', 'name' => 'Toko Alpha', 'is_active' => true]);
            BusinessUnit::create(['code' => 'UNT-002', 'name' => 'Toko Beta', 'is_active' => true]);
        });

        Livewire::test(BusinessUnitList::class)
            ->set('search', 'Alpha')
            ->assertSee('Toko Alpha')
            ->assertDontSee('Toko Beta');
    }

    /** @test */
    public function business_unit_list_can_filter_by_status()
    {
        BusinessUnit::withoutEvents(function () {
            BusinessUnit::create(['code' => 'UNT-001', 'name' => 'Active Unit', 'is_active' => true]);
            BusinessUnit::create(['code' => 'UNT-002', 'name' => 'Inactive Unit', 'is_active' => false]);
        });

        Livewire::test(BusinessUnitList::class)
            ->set('filterStatus', '1')
            ->assertSee('Active Unit')
            ->assertDontSee('Inactive Unit');

        Livewire::test(BusinessUnitList::class)
            ->set('filterStatus', '0')
            ->assertSee('Inactive Unit')
            ->assertDontSee('Active Unit');
    }

    /** @test */
    public function business_unit_list_can_sort()
    {
        BusinessUnit::withoutEvents(function () {
            BusinessUnit::create(['code' => 'UNT-002', 'name' => 'Beta', 'is_active' => true]);
            BusinessUnit::create(['code' => 'UNT-001', 'name' => 'Alpha', 'is_active' => true]);
        });

        Livewire::test(BusinessUnitList::class)
            ->call('sortBy', 'name')
            ->assertSeeInOrder(['Alpha', 'Beta']);
    }

    /** @test */
    public function business_unit_list_can_toggle_status()
    {
        $unit = BusinessUnit::withoutEvents(function () {
            return BusinessUnit::create(['code' => 'UNT-001', 'name' => 'Test Unit', 'is_active' => true]);
        });

        Livewire::test(BusinessUnitList::class)
            ->call('toggleStatus', $unit->id)
            ->assertDispatched('alert');

        $this->assertFalse($unit->fresh()->is_active);
    }

    /** @test */
    public function business_unit_list_can_delete_unit_without_users()
    {
        $unit = BusinessUnit::withoutEvents(function () {
            return BusinessUnit::create(['code' => 'UNT-001', 'name' => 'To Delete', 'is_active' => true]);
        });

        Livewire::test(BusinessUnitList::class)
            ->call('deleteUnit', $unit->id)
            ->assertDispatched('alert');

        $this->assertSoftDeleted('business_units', ['id' => $unit->id]);
    }

    /** @test */
    public function business_unit_list_prevents_deleting_unit_with_users()
    {
        $unit = BusinessUnit::withoutEvents(function () {
            return BusinessUnit::create(['code' => 'UNT-001', 'name' => 'Has Users', 'is_active' => true]);
        });

        User::withoutEvents(function () use ($unit) {
            return User::factory()->create(['business_unit_id' => $unit->id]);
        });

        Livewire::test(BusinessUnitList::class)
            ->call('deleteUnit', $unit->id)
            ->assertDispatched('alert', type: 'error');

        $this->assertDatabaseHas('business_units', ['id' => $unit->id, 'deleted_at' => null]);
    }

    // ==================== BUSINESS UNIT FORM TESTS ====================

    /** @test */
    public function business_unit_form_renders_successfully()
    {
        Livewire::test(BusinessUnitForm::class)
            ->assertStatus(200);
    }

    /** @test */
    public function business_unit_form_initializes_coa_mappings()
    {
        $component = Livewire::test(BusinessUnitForm::class);
        $definitions = BusinessUnitCoaMapping::getAccountKeyDefinitions();

        foreach ($definitions as $type => $keys) {
            foreach ($keys as $def) {
                $component->assertSet("coaMappings.{$def['key']}", '');
            }
        }
    }

    /** @test */
    public function business_unit_form_can_create_unit()
    {
        Livewire::test(BusinessUnitForm::class)
            ->set('code', 'NEW-001')
            ->set('name', 'New Business Unit')
            ->set('owner_name', 'John Doe')
            ->set('city', 'Jakarta')
            ->set('business_type', 'toko')
            ->set('is_active', true)
            ->call('save')
            ->assertDispatched('alert', type: 'success');

        $this->assertDatabaseHas('business_units', [
            'code' => 'NEW-001',
            'name' => 'New Business Unit',
            'owner_name' => 'John Doe',
            'city' => 'Jakarta',
            'business_type' => 'toko',
        ]);
    }

    /** @test */
    public function business_unit_form_validates_required_fields()
    {
        Livewire::test(BusinessUnitForm::class)
            ->set('code', '')
            ->set('name', '')
            ->call('save')
            ->assertHasErrors(['code', 'name']);
    }

    /** @test */
    public function business_unit_form_validates_unique_code()
    {
        BusinessUnit::withoutEvents(function () {
            BusinessUnit::create(['code' => 'EXIST-01', 'name' => 'Existing', 'is_active' => true]);
        });

        Livewire::test(BusinessUnitForm::class)
            ->set('code', 'EXIST-01')
            ->set('name', 'Another Unit')
            ->call('save')
            ->assertHasErrors(['code']);
    }

    /** @test */
    public function business_unit_form_can_edit_unit()
    {
        $unit = BusinessUnit::withoutEvents(function () {
            return BusinessUnit::create([
                'code' => 'UNT-001',
                'name' => 'Original Name',
                'is_active' => true,
            ]);
        });

        Livewire::test(BusinessUnitForm::class, ['unitId' => $unit->id])
            ->assertSet('isEditing', true)
            ->assertSet('code', 'UNT-001')
            ->set('name', 'Updated Name')
            ->call('save')
            ->assertDispatched('alert', type: 'success');

        $this->assertDatabaseHas('business_units', [
            'id' => $unit->id,
            'name' => 'Updated Name',
        ]);
    }

    /** @test */
    public function business_unit_form_can_switch_tabs()
    {
        Livewire::test(BusinessUnitForm::class)
            ->assertSet('activeTab', 'profile')
            ->call('setActiveTab', 'coa_aktiva')
            ->assertSet('activeTab', 'coa_aktiva')
            ->call('setActiveTab', 'coa_beban')
            ->assertSet('activeTab', 'coa_beban');
    }

    /** @test */
    public function business_unit_form_saves_coa_mappings()
    {
        $coa = COA::withoutEvents(function () {
            return COA::create([
                'code' => '1-0001',
                'name' => 'Kas Utama',
                'type' => 'aktiva',
                'is_active' => true,
                'is_leaf_account' => true,
                'level' => 2,
            ]);
        });

        Livewire::test(BusinessUnitForm::class)
            ->set('code', 'UNT-COA')
            ->set('name', 'Unit With COA')
            ->set('coaMappings.kas_utama', (string) $coa->id)
            ->call('save')
            ->assertDispatched('alert', type: 'success');

        $unit = BusinessUnit::where('code', 'UNT-COA')->first();
        $this->assertNotNull($unit);
        $this->assertDatabaseHas('business_unit_coa_mappings', [
            'business_unit_id' => $unit->id,
            'account_key' => 'kas_utama',
            'coa_id' => $coa->id,
        ]);
    }

    /** @test */
    public function business_unit_form_removes_empty_coa_mappings()
    {
        $coa = COA::withoutEvents(function () {
            return COA::create([
                'code' => '1-0001',
                'name' => 'Kas Utama',
                'type' => 'aktiva',
                'is_active' => true,
                'is_leaf_account' => true,
                'level' => 2,
            ]);
        });

        $unit = BusinessUnit::withoutEvents(function () {
            return BusinessUnit::create(['code' => 'UNT-001', 'name' => 'Test', 'is_active' => true]);
        });

        BusinessUnitCoaMapping::create([
            'business_unit_id' => $unit->id,
            'account_key' => 'kas_utama',
            'label' => 'Kas Utama',
            'coa_id' => $coa->id,
        ]);

        Livewire::test(BusinessUnitForm::class, ['unitId' => $unit->id])
            ->set('coaMappings.kas_utama', '')
            ->call('save');

        $this->assertDatabaseMissing('business_unit_coa_mappings', [
            'business_unit_id' => $unit->id,
            'account_key' => 'kas_utama',
        ]);
    }

    /** @test */
    public function business_unit_form_loads_existing_mappings_on_edit()
    {
        $coa = COA::withoutEvents(function () {
            return COA::create([
                'code' => '1-0001',
                'name' => 'Kas Utama',
                'type' => 'aktiva',
                'is_active' => true,
                'is_leaf_account' => true,
                'level' => 2,
            ]);
        });

        $unit = BusinessUnit::withoutEvents(function () {
            return BusinessUnit::create(['code' => 'UNT-001', 'name' => 'Test', 'is_active' => true]);
        });

        BusinessUnitCoaMapping::create([
            'business_unit_id' => $unit->id,
            'account_key' => 'kas_utama',
            'label' => 'Kas Utama',
            'coa_id' => $coa->id,
        ]);

        Livewire::test(BusinessUnitForm::class, ['unitId' => $unit->id])
            ->assertSet('coaMappings.kas_utama', (string) $coa->id);
    }

    /** @test */
    public function business_unit_form_validates_email_format()
    {
        Livewire::test(BusinessUnitForm::class)
            ->set('code', 'UNT-001')
            ->set('name', 'Test')
            ->set('email', 'invalid-email')
            ->call('save')
            ->assertHasErrors(['email']);
    }

    /** @test */
    public function business_unit_unique_code_allows_editing_same_unit()
    {
        $unit = BusinessUnit::withoutEvents(function () {
            return BusinessUnit::create(['code' => 'UNT-001', 'name' => 'Test', 'is_active' => true]);
        });

        Livewire::test(BusinessUnitForm::class, ['unitId' => $unit->id])
            ->set('name', 'Updated Name')
            ->call('save')
            ->assertHasNoErrors(['code']);
    }
}
