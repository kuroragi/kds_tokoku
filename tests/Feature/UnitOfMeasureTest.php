<?php

namespace Tests\Feature;

use App\Livewire\StockManagement\UnitOfMeasureForm;
use App\Livewire\StockManagement\UnitOfMeasureList;
use App\Models\BusinessUnit;
use App\Models\CategoryGroup;
use App\Models\Stock;
use App\Models\StockCategory;
use App\Models\UnitOfMeasure;
use App\Models\User;
use App\Services\BusinessUnitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UnitOfMeasureTest extends TestCase
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

    protected function createMeasure(array $overrides = []): UnitOfMeasure
    {
        return UnitOfMeasure::withoutEvents(function () use ($overrides) {
            return UnitOfMeasure::create(array_merge([
                'business_unit_id' => $this->unit->id,
                'code' => 'PCS',
                'name' => 'Pieces',
                'symbol' => 'pcs',
                'is_system_default' => false,
                'is_active' => true,
            ], $overrides));
        });
    }

    // ==================== PAGE ACCESS TESTS ====================

    /** @test */
    public function unit_of_measure_index_page_is_accessible()
    {
        $response = $this->get(route('unit-of-measure.index'));
        $response->assertStatus(200);
        $response->assertSee('Satuan');
    }

    /** @test */
    public function guest_cannot_access_unit_of_measure_page()
    {
        auth()->logout();
        $this->get(route('unit-of-measure.index'))->assertRedirect(route('login'));
    }

    // ==================== LIST COMPONENT TESTS ====================

    /** @test */
    public function unit_of_measure_list_renders_successfully()
    {
        Livewire::test(UnitOfMeasureList::class)
            ->assertStatus(200);
    }

    /** @test */
    public function unit_of_measure_list_shows_measures()
    {
        $this->createMeasure(['code' => 'PCS', 'name' => 'Pieces']);
        $this->createMeasure(['code' => 'KG', 'name' => 'Kilogram']);

        Livewire::test(UnitOfMeasureList::class)
            ->assertSee('Pieces')
            ->assertSee('Kilogram');
    }

    /** @test */
    public function unit_of_measure_list_can_search()
    {
        $this->createMeasure(['code' => 'PCS', 'name' => 'Pieces']);
        $this->createMeasure(['code' => 'KG', 'name' => 'Kilogram']);

        Livewire::test(UnitOfMeasureList::class)
            ->set('search', 'Pieces')
            ->assertSee('Pieces')
            ->assertDontSee('Kilogram');
    }

    /** @test */
    public function unit_of_measure_list_can_filter_by_unit()
    {
        $unit2 = BusinessUnit::withoutEvents(function () {
            return BusinessUnit::create(['code' => 'UNT-002', 'name' => 'Unit 2', 'is_active' => true]);
        });

        $this->createMeasure(['code' => 'PCS', 'name' => 'Satuan Unit 1']);
        UnitOfMeasure::withoutEvents(function () use ($unit2) {
            return UnitOfMeasure::create([
                'business_unit_id' => $unit2->id,
                'code' => 'KG',
                'name' => 'Satuan Unit 2',
                'is_active' => true,
            ]);
        });

        Livewire::test(UnitOfMeasureList::class)
            ->set('filterUnit', $this->unit->id)
            ->assertSee('Satuan Unit 1')
            ->assertDontSee('Satuan Unit 2');
    }

    /** @test */
    public function unit_of_measure_list_can_filter_by_status()
    {
        $this->createMeasure(['code' => 'PCS', 'name' => 'Active Measure', 'is_active' => true]);
        $this->createMeasure(['code' => 'KG', 'name' => 'Inactive Measure', 'is_active' => false]);

        Livewire::test(UnitOfMeasureList::class)
            ->set('filterStatus', '1')
            ->assertSee('Active Measure')
            ->assertDontSee('Inactive Measure');
    }

    /** @test */
    public function unit_of_measure_list_can_sort()
    {
        $this->createMeasure(['code' => 'ZZZ', 'name' => 'Zebra']);
        $this->createMeasure(['code' => 'AAA', 'name' => 'Alpha']);

        Livewire::test(UnitOfMeasureList::class)
            ->call('sortBy', 'name')
            ->assertSeeInOrder(['Alpha', 'Zebra']);
    }

    /** @test */
    public function unit_of_measure_list_can_toggle_status()
    {
        $measure = $this->createMeasure();

        Livewire::test(UnitOfMeasureList::class)
            ->call('toggleStatus', $measure->id)
            ->assertDispatched('alert');

        $this->assertFalse($measure->fresh()->is_active);
    }

    /** @test */
    public function unit_of_measure_list_can_delete_measure_without_stocks()
    {
        $measure = $this->createMeasure();

        Livewire::test(UnitOfMeasureList::class)
            ->call('deleteMeasure', $measure->id)
            ->assertDispatched('alert');

        $this->assertSoftDeleted('unit_of_measures', ['id' => $measure->id]);
    }

    /** @test */
    public function unit_of_measure_list_prevents_deleting_measure_with_stocks()
    {
        $measure = $this->createMeasure();

        $category = StockCategory::withoutEvents(function () {
            return StockCategory::create([
                'business_unit_id' => $this->unit->id,
                'code' => 'CAT-001',
                'name' => 'Kategori Test',
                'type' => 'barang',
                'is_active' => true,
            ]);
        });

        $group = CategoryGroup::withoutEvents(function () use ($category) {
            return CategoryGroup::create([
                'business_unit_id' => $this->unit->id,
                'stock_category_id' => $category->id,
                'code' => 'GRP-001',
                'name' => 'Grup Test',
                'is_active' => true,
            ]);
        });

        Stock::withoutEvents(function () use ($group, $measure) {
            return Stock::create([
                'business_unit_id' => $this->unit->id,
                'category_group_id' => $group->id,
                'unit_of_measure_id' => $measure->id,
                'code' => 'STK-001',
                'name' => 'Test Stock',
                'buy_price' => 1000,
                'sell_price' => 1500,
                'min_stock' => 5,
                'current_stock' => 10,
                'is_active' => true,
            ]);
        });

        Livewire::test(UnitOfMeasureList::class)
            ->call('deleteMeasure', $measure->id)
            ->assertDispatched('alert', type: 'error');

        $this->assertDatabaseHas('unit_of_measures', ['id' => $measure->id, 'deleted_at' => null]);
    }

    /** @test */
    public function unit_of_measure_list_can_duplicate_defaults()
    {
        Livewire::test(UnitOfMeasureList::class)
            ->call('duplicateDefaults', $this->unit->id)
            ->assertDispatched('alert', type: 'success');

        $count = UnitOfMeasure::where('business_unit_id', $this->unit->id)->count();
        $this->assertEquals(count(UnitOfMeasure::getSystemDefaults()), $count);
    }

    /** @test */
    public function unit_of_measure_list_duplicate_defaults_skips_existing()
    {
        // Create one default first
        $defaults = UnitOfMeasure::getSystemDefaults();
        UnitOfMeasure::withoutEvents(function () use ($defaults) {
            return UnitOfMeasure::create([
                'business_unit_id' => $this->unit->id,
                'code' => $defaults[0]['code'],
                'name' => $defaults[0]['name'],
                'symbol' => $defaults[0]['symbol'],
                'is_system_default' => false,
                'is_active' => true,
            ]);
        });

        Livewire::test(UnitOfMeasureList::class)
            ->call('duplicateDefaults', $this->unit->id)
            ->assertDispatched('alert');

        // Should still equal total defaults (skipped existing)
        $count = UnitOfMeasure::where('business_unit_id', $this->unit->id)->count();
        $this->assertEquals(count($defaults), $count);
    }

    // ==================== FORM COMPONENT TESTS ====================

    /** @test */
    public function unit_of_measure_form_renders_successfully()
    {
        Livewire::test(UnitOfMeasureForm::class)
            ->assertStatus(200);
    }

    /** @test */
    public function unit_of_measure_form_can_open_modal()
    {
        Livewire::test(UnitOfMeasureForm::class)
            ->call('openUnitOfMeasureModal')
            ->assertSet('showModal', true)
            ->assertSet('isEditing', false);
    }

    /** @test */
    public function unit_of_measure_form_can_create_measure()
    {
        Livewire::test(UnitOfMeasureForm::class)
            ->call('openUnitOfMeasureModal')
            ->set('business_unit_id', $this->unit->id)
            ->set('code', 'BOX')
            ->set('name', 'Box')
            ->set('symbol', 'box')
            ->call('save')
            ->assertDispatched('alert', type: 'success')
            ->assertDispatched('refreshUnitOfMeasureList')
            ->assertSet('showModal', false);

        $this->assertDatabaseHas('unit_of_measures', [
            'business_unit_id' => $this->unit->id,
            'code' => 'BOX',
            'name' => 'Box',
            'symbol' => 'box',
            'is_system_default' => false,
        ]);
    }

    /** @test */
    public function unit_of_measure_form_can_edit_measure()
    {
        $measure = $this->createMeasure();

        Livewire::test(UnitOfMeasureForm::class)
            ->call('editUnitOfMeasure', $measure->id)
            ->assertSet('showModal', true)
            ->assertSet('isEditing', true)
            ->assertSet('code', 'PCS')
            ->set('name', 'Updated Pieces')
            ->call('save')
            ->assertDispatched('alert', type: 'success');

        $this->assertDatabaseHas('unit_of_measures', [
            'id' => $measure->id,
            'name' => 'Updated Pieces',
        ]);
    }

    /** @test */
    public function unit_of_measure_form_validates_required_fields()
    {
        Livewire::test(UnitOfMeasureForm::class)
            ->call('openUnitOfMeasureModal')
            ->set('business_unit_id', '')
            ->set('code', '')
            ->set('name', '')
            ->call('save')
            ->assertHasErrors(['business_unit_id', 'code', 'name']);
    }

    /** @test */
    public function unit_of_measure_form_validates_unique_code_per_unit()
    {
        $this->createMeasure(['code' => 'PCS']);

        Livewire::test(UnitOfMeasureForm::class)
            ->call('openUnitOfMeasureModal')
            ->set('business_unit_id', $this->unit->id)
            ->set('code', 'PCS')
            ->set('name', 'Duplicate')
            ->call('save')
            ->assertHasErrors(['code']);
    }

    /** @test */
    public function unit_of_measure_form_allows_same_code_in_different_units()
    {
        $this->createMeasure(['code' => 'PCS']);

        $unit2 = BusinessUnit::withoutEvents(function () {
            return BusinessUnit::create(['code' => 'UNT-002', 'name' => 'Unit 2', 'is_active' => true]);
        });

        Livewire::test(UnitOfMeasureForm::class)
            ->call('openUnitOfMeasureModal')
            ->set('business_unit_id', $unit2->id)
            ->set('code', 'PCS')
            ->set('name', 'Pieces Unit 2')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('unit_of_measures', 2);
    }

    /** @test */
    public function unit_of_measure_form_can_close_modal()
    {
        Livewire::test(UnitOfMeasureForm::class)
            ->call('openUnitOfMeasureModal')
            ->assertSet('showModal', true)
            ->call('closeModal')
            ->assertSet('showModal', false);
    }

    // ==================== MODEL TESTS ====================

    /** @test */
    public function unit_of_measure_has_system_defaults()
    {
        $defaults = UnitOfMeasure::getSystemDefaults();
        $this->assertNotEmpty($defaults);
        $this->assertCount(12, $defaults);
    }

    /** @test */
    public function unit_of_measure_can_duplicate_defaults_for_business_unit()
    {
        UnitOfMeasure::duplicateDefaultsForBusinessUnit($this->unit->id);

        $count = UnitOfMeasure::where('business_unit_id', $this->unit->id)->count();
        $this->assertEquals(count(UnitOfMeasure::getSystemDefaults()), $count);
    }

    /** @test */
    public function unit_of_measure_duplicate_does_not_create_duplicates()
    {
        UnitOfMeasure::duplicateDefaultsForBusinessUnit($this->unit->id);
        UnitOfMeasure::duplicateDefaultsForBusinessUnit($this->unit->id);

        $count = UnitOfMeasure::where('business_unit_id', $this->unit->id)->count();
        $this->assertEquals(count(UnitOfMeasure::getSystemDefaults()), $count);
    }

    /** @test */
    public function unit_of_measure_belongs_to_business_unit()
    {
        $measure = $this->createMeasure();
        $this->assertInstanceOf(BusinessUnit::class, $measure->businessUnit);
    }

    /** @test */
    public function unit_of_measure_active_scope_works()
    {
        $this->createMeasure(['code' => 'PCS', 'is_active' => true]);
        $this->createMeasure(['code' => 'KG', 'is_active' => false]);

        $this->assertEquals(1, UnitOfMeasure::active()->where('business_unit_id', $this->unit->id)->count());
    }

    /** @test */
    public function unit_of_measure_system_defaults_scope_works()
    {
        // Create a system default
        UnitOfMeasure::withoutEvents(function () {
            return UnitOfMeasure::create([
                'business_unit_id' => null,
                'code' => 'SYS-PCS',
                'name' => 'System Pieces',
                'is_system_default' => true,
                'is_active' => true,
            ]);
        });

        // Create a unit-level measure
        $this->createMeasure();

        $this->assertEquals(1, UnitOfMeasure::systemDefaults()->count());
    }

    // ==================== BUSINESS UNIT SCOPING TESTS ====================

    /** @test */
    public function non_superadmin_list_only_sees_own_unit_measures()
    {
        $unit2 = BusinessUnit::withoutEvents(fn() => BusinessUnit::create(['code' => 'UNT-002', 'name' => 'Unit 2', 'is_active' => true]));

        $this->createMeasure(['code' => 'PCS', 'name' => 'Pieces Unit 1']);
        UnitOfMeasure::withoutEvents(fn() => UnitOfMeasure::create([
            'business_unit_id' => $unit2->id, 'code' => 'KG', 'name' => 'Kilogram Unit 2', 'is_active' => true,
        ]));

        $regularUser = User::withoutEvents(fn() => User::factory()->create(['business_unit_id' => $this->unit->id]));
        $this->actingAs($regularUser);

        Livewire::test(UnitOfMeasureList::class)
            ->assertSee('Pieces Unit 1')
            ->assertDontSee('Kilogram Unit 2');
    }

    /** @test */
    public function non_superadmin_form_auto_fills_business_unit_id()
    {
        $regularUser = User::withoutEvents(fn() => User::factory()->create(['business_unit_id' => $this->unit->id]));
        $this->actingAs($regularUser);

        Livewire::test(UnitOfMeasureForm::class)
            ->call('openUnitOfMeasureModal')
            ->assertSet('business_unit_id', $this->unit->id);
    }

    /** @test */
    public function non_superadmin_save_uses_own_business_unit_id()
    {
        $regularUser = User::withoutEvents(fn() => User::factory()->create(['business_unit_id' => $this->unit->id]));
        $this->actingAs($regularUser);

        Livewire::test(UnitOfMeasureForm::class)
            ->call('openUnitOfMeasureModal')
            ->set('code', 'UOM-AUTO')
            ->set('name', 'Auto Measure')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('unit_of_measures', [
            'code' => 'UOM-AUTO',
            'business_unit_id' => $this->unit->id,
        ]);
    }
}
