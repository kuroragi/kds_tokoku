<?php

namespace Tests\Feature;

use App\Livewire\NameCard\PositionForm;
use App\Livewire\NameCard\PositionList;
use App\Models\BusinessUnit;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PositionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected BusinessUnit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'superadmin']);

        $this->user = User::withoutEvents(function () {
            return User::factory()->create();
        });
        $this->user->assignRole('superadmin');
        $this->actingAs($this->user);

        $this->unit = BusinessUnit::withoutEvents(function () {
            return BusinessUnit::create([
                'code' => 'UNT-001',
                'name' => 'Test Unit',
                'is_active' => true,
            ]);
        });
    }

    protected function createPosition(array $overrides = []): Position
    {
        return Position::withoutEvents(function () use ($overrides) {
            return Position::create(array_merge([
                'business_unit_id' => $this->unit->id,
                'code' => 'MGR',
                'name' => 'Manager',
                'is_system_default' => false,
                'is_active' => true,
            ], $overrides));
        });
    }

    // ==================== PAGE ACCESS TESTS ====================

    /** @test */
    public function position_index_page_is_accessible()
    {
        $response = $this->get(route('position.index'));
        $response->assertStatus(200);
        $response->assertSee('Jabatan');
    }

    /** @test */
    public function guest_cannot_access_position_page()
    {
        auth()->logout();
        $this->get(route('position.index'))->assertRedirect(route('login'));
    }

    // ==================== LIST COMPONENT TESTS ====================

    /** @test */
    public function position_list_renders_successfully()
    {
        Livewire::test(PositionList::class)
            ->assertStatus(200);
    }

    /** @test */
    public function position_list_shows_positions()
    {
        $this->createPosition(['code' => 'MGR', 'name' => 'Manager']);
        $this->createPosition(['code' => 'ADM', 'name' => 'Admin']);

        Livewire::test(PositionList::class)
            ->assertSee('Manager')
            ->assertSee('Admin');
    }

    /** @test */
    public function position_list_can_search()
    {
        $this->createPosition(['code' => 'MGR', 'name' => 'Manager']);
        $this->createPosition(['code' => 'ADM', 'name' => 'Admin']);

        Livewire::test(PositionList::class)
            ->set('search', 'Manager')
            ->assertSee('Manager')
            ->assertDontSee('Admin');
    }

    /** @test */
    public function position_list_can_filter_by_unit()
    {
        $unit2 = BusinessUnit::withoutEvents(fn() =>
            BusinessUnit::create(['code' => 'UNT-002', 'name' => 'Unit 2', 'is_active' => true])
        );

        $this->createPosition(['code' => 'MGR', 'name' => 'Manager Unit 1']);
        Position::withoutEvents(fn() => Position::create([
            'business_unit_id' => $unit2->id, 'code' => 'ADM', 'name' => 'Admin Unit 2', 'is_active' => true,
        ]));

        Livewire::test(PositionList::class)
            ->set('filterUnit', $this->unit->id)
            ->assertSee('Manager Unit 1')
            ->assertDontSee('Admin Unit 2');
    }

    /** @test */
    public function position_list_can_filter_by_status()
    {
        $this->createPosition(['code' => 'MGR', 'name' => 'Active Position', 'is_active' => true]);
        $this->createPosition(['code' => 'ADM', 'name' => 'Inactive Position', 'is_active' => false]);

        Livewire::test(PositionList::class)
            ->set('filterStatus', '1')
            ->assertSee('Active Position')
            ->assertDontSee('Inactive Position');
    }

    /** @test */
    public function position_list_can_sort()
    {
        $this->createPosition(['code' => 'ZZZ', 'name' => 'Zebra']);
        $this->createPosition(['code' => 'AAA', 'name' => 'Alpha']);

        Livewire::test(PositionList::class)
            ->call('sortBy', 'name')
            ->assertSeeInOrder(['Alpha', 'Zebra']);
    }

    /** @test */
    public function position_list_can_toggle_status()
    {
        $position = $this->createPosition();

        Livewire::test(PositionList::class)
            ->call('toggleStatus', $position->id)
            ->assertDispatched('alert');

        $this->assertFalse($position->fresh()->is_active);
    }

    /** @test */
    public function position_list_can_delete_position_without_employees()
    {
        $position = $this->createPosition();

        Livewire::test(PositionList::class)
            ->call('deletePosition', $position->id)
            ->assertDispatched('alert');

        $this->assertSoftDeleted('positions', ['id' => $position->id]);
    }

    /** @test */
    public function position_list_prevents_deleting_position_with_employees()
    {
        $position = $this->createPosition();

        Employee::withoutEvents(fn() => Employee::create([
            'business_unit_id' => $this->unit->id,
            'position_id' => $position->id,
            'code' => 'EMP-001',
            'name' => 'Test Employee',
            'is_active' => true,
        ]));

        Livewire::test(PositionList::class)
            ->call('deletePosition', $position->id)
            ->assertDispatched('alert', type: 'error');

        $this->assertDatabaseHas('positions', ['id' => $position->id, 'deleted_at' => null]);
    }

    /** @test */
    public function position_list_can_duplicate_defaults()
    {
        Livewire::test(PositionList::class)
            ->call('duplicateDefaults', $this->unit->id)
            ->assertDispatched('alert', type: 'success');

        $count = Position::where('business_unit_id', $this->unit->id)->count();
        $this->assertEquals(count(Position::getSystemDefaults()), $count);
    }

    /** @test */
    public function position_list_duplicate_defaults_skips_existing()
    {
        $defaults = Position::getSystemDefaults();
        Position::withoutEvents(fn() => Position::create([
            'business_unit_id' => $this->unit->id,
            'code' => $defaults[0]['code'],
            'name' => $defaults[0]['name'],
            'is_system_default' => false,
            'is_active' => true,
        ]));

        Livewire::test(PositionList::class)
            ->call('duplicateDefaults', $this->unit->id)
            ->assertDispatched('alert');

        $count = Position::where('business_unit_id', $this->unit->id)->count();
        $this->assertEquals(count($defaults), $count);
    }

    // ==================== FORM COMPONENT TESTS ====================

    /** @test */
    public function position_form_renders_successfully()
    {
        Livewire::test(PositionForm::class)
            ->assertStatus(200);
    }

    /** @test */
    public function position_form_can_open_modal()
    {
        Livewire::test(PositionForm::class)
            ->call('openPositionModal')
            ->assertSet('showModal', true)
            ->assertSet('isEditing', false);
    }

    /** @test */
    public function position_form_can_create_position()
    {
        Livewire::test(PositionForm::class)
            ->call('openPositionModal')
            ->set('business_unit_id', $this->unit->id)
            ->set('code', 'SPV')
            ->set('name', 'Supervisor')
            ->call('save')
            ->assertDispatched('alert', type: 'success')
            ->assertDispatched('refreshPositionList')
            ->assertSet('showModal', false);

        $this->assertDatabaseHas('positions', [
            'business_unit_id' => $this->unit->id,
            'code' => 'SPV',
            'name' => 'Supervisor',
            'is_system_default' => false,
        ]);
    }

    /** @test */
    public function position_form_can_edit_position()
    {
        $position = $this->createPosition();

        Livewire::test(PositionForm::class)
            ->call('editPosition', $position->id)
            ->assertSet('showModal', true)
            ->assertSet('isEditing', true)
            ->assertSet('code', 'MGR')
            ->set('name', 'Senior Manager')
            ->call('save')
            ->assertDispatched('alert', type: 'success');

        $this->assertDatabaseHas('positions', [
            'id' => $position->id,
            'name' => 'Senior Manager',
        ]);
    }

    /** @test */
    public function position_form_validates_required_fields()
    {
        Livewire::test(PositionForm::class)
            ->call('openPositionModal')
            ->set('business_unit_id', '')
            ->set('code', '')
            ->set('name', '')
            ->call('save')
            ->assertHasErrors(['business_unit_id', 'code', 'name']);
    }

    /** @test */
    public function position_form_validates_unique_code_per_unit()
    {
        $this->createPosition(['code' => 'MGR']);

        Livewire::test(PositionForm::class)
            ->call('openPositionModal')
            ->set('business_unit_id', $this->unit->id)
            ->set('code', 'MGR')
            ->set('name', 'Duplicate')
            ->call('save')
            ->assertHasErrors(['code']);
    }

    /** @test */
    public function position_form_allows_same_code_in_different_units()
    {
        $this->createPosition(['code' => 'MGR']);

        $unit2 = BusinessUnit::withoutEvents(fn() =>
            BusinessUnit::create(['code' => 'UNT-002', 'name' => 'Unit 2', 'is_active' => true])
        );

        Livewire::test(PositionForm::class)
            ->call('openPositionModal')
            ->set('business_unit_id', $unit2->id)
            ->set('code', 'MGR')
            ->set('name', 'Manager Unit 2')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('positions', 2);
    }

    /** @test */
    public function position_form_can_close_modal()
    {
        Livewire::test(PositionForm::class)
            ->call('openPositionModal')
            ->assertSet('showModal', true)
            ->call('closeModal')
            ->assertSet('showModal', false);
    }

    // ==================== MODEL TESTS ====================

    /** @test */
    public function position_has_system_defaults()
    {
        $defaults = Position::getSystemDefaults();
        $this->assertNotEmpty($defaults);
        $this->assertCount(10, $defaults);
    }

    /** @test */
    public function position_can_duplicate_defaults_for_business_unit()
    {
        Position::duplicateDefaultsForBusinessUnit($this->unit->id);

        $count = Position::where('business_unit_id', $this->unit->id)->count();
        $this->assertEquals(count(Position::getSystemDefaults()), $count);
    }

    /** @test */
    public function position_duplicate_does_not_create_duplicates()
    {
        Position::duplicateDefaultsForBusinessUnit($this->unit->id);
        Position::duplicateDefaultsForBusinessUnit($this->unit->id);

        $count = Position::where('business_unit_id', $this->unit->id)->count();
        $this->assertEquals(count(Position::getSystemDefaults()), $count);
    }

    /** @test */
    public function position_belongs_to_business_unit()
    {
        $position = $this->createPosition();
        $this->assertInstanceOf(BusinessUnit::class, $position->businessUnit);
    }

    /** @test */
    public function position_active_scope_works()
    {
        $this->createPosition(['code' => 'MGR', 'is_active' => true]);
        $this->createPosition(['code' => 'ADM', 'is_active' => false]);

        $this->assertEquals(1, Position::active()->where('business_unit_id', $this->unit->id)->count());
    }

    /** @test */
    public function position_system_defaults_scope_works()
    {
        Position::withoutEvents(fn() => Position::create([
            'business_unit_id' => null,
            'code' => 'SYS-MGR',
            'name' => 'System Manager',
            'is_system_default' => true,
            'is_active' => true,
        ]));

        $this->createPosition();

        $this->assertEquals(1, Position::systemDefaults()->count());
    }

    // ==================== BUSINESS UNIT SCOPING TESTS ====================

    /** @test */
    public function non_superadmin_list_only_sees_own_unit_positions()
    {
        $unit2 = BusinessUnit::withoutEvents(fn() => BusinessUnit::create(['code' => 'UNT-002', 'name' => 'Unit 2', 'is_active' => true]));

        $this->createPosition(['code' => 'MGR', 'name' => 'Manager Unit 1']);
        Position::withoutEvents(fn() => Position::create([
            'business_unit_id' => $unit2->id, 'code' => 'ADM', 'name' => 'Admin Unit 2', 'is_active' => true,
        ]));

        $regularUser = User::withoutEvents(fn() => User::factory()->create(['business_unit_id' => $this->unit->id]));
        $this->actingAs($regularUser);

        Livewire::test(PositionList::class)
            ->assertSee('Manager Unit 1')
            ->assertDontSee('Admin Unit 2');
    }

    /** @test */
    public function non_superadmin_form_auto_fills_business_unit_id()
    {
        $regularUser = User::withoutEvents(fn() => User::factory()->create(['business_unit_id' => $this->unit->id]));
        $this->actingAs($regularUser);

        Livewire::test(PositionForm::class)
            ->call('openPositionModal')
            ->assertSet('business_unit_id', $this->unit->id);
    }

    /** @test */
    public function non_superadmin_save_uses_own_business_unit_id()
    {
        $regularUser = User::withoutEvents(fn() => User::factory()->create(['business_unit_id' => $this->unit->id]));
        $this->actingAs($regularUser);

        Livewire::test(PositionForm::class)
            ->call('openPositionModal')
            ->set('code', 'POS-AUTO')
            ->set('name', 'Auto Position')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('positions', [
            'code' => 'POS-AUTO',
            'business_unit_id' => $this->unit->id,
        ]);
    }
}
