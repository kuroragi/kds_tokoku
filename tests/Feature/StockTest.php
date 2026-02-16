<?php

namespace Tests\Feature;

use App\Livewire\StockManagement\StockForm;
use App\Livewire\StockManagement\StockList;
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

class StockTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected BusinessUnit $unit;
    protected StockCategory $category;
    protected CategoryGroup $group;
    protected UnitOfMeasure $measure;

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

        $this->category = StockCategory::withoutEvents(function () {
            return StockCategory::create([
                'business_unit_id' => $this->unit->id,
                'code' => 'CAT-001',
                'name' => 'Kategori Barang',
                'type' => 'barang',
                'is_active' => true,
            ]);
        });

        $this->group = CategoryGroup::withoutEvents(function () {
            return CategoryGroup::create([
                'business_unit_id' => $this->unit->id,
                'stock_category_id' => $this->category->id,
                'code' => 'GRP-001',
                'name' => 'Grup Aksesoris',
                'is_active' => true,
            ]);
        });

        $this->measure = UnitOfMeasure::withoutEvents(function () {
            return UnitOfMeasure::create([
                'business_unit_id' => $this->unit->id,
                'code' => 'PCS',
                'name' => 'Pieces',
                'symbol' => 'pcs',
                'is_active' => true,
            ]);
        });
    }

    protected function createStock(array $overrides = []): Stock
    {
        return Stock::withoutEvents(function () use ($overrides) {
            return Stock::create(array_merge([
                'business_unit_id' => $this->unit->id,
                'category_group_id' => $this->group->id,
                'unit_of_measure_id' => $this->measure->id,
                'code' => 'STK-001',
                'name' => 'Stok Test',
                'buy_price' => 10000,
                'sell_price' => 15000,
                'min_stock' => 5,
                'current_stock' => 10,
                'is_active' => true,
            ], $overrides));
        });
    }

    // ==================== PAGE ACCESS TESTS ====================

    /** @test */
    public function stock_index_page_is_accessible()
    {
        $response = $this->get(route('stock.index'));
        $response->assertStatus(200);
        $response->assertSee('Stok');
    }

    /** @test */
    public function guest_cannot_access_stock_page()
    {
        auth()->logout();
        $this->get(route('stock.index'))->assertRedirect(route('login'));
    }

    // ==================== LIST COMPONENT TESTS ====================

    /** @test */
    public function stock_list_renders_successfully()
    {
        Livewire::test(StockList::class)
            ->assertStatus(200);
    }

    /** @test */
    public function stock_list_shows_stocks()
    {
        $this->createStock(['code' => 'STK-001', 'name' => 'Kartu Perdana']);
        $this->createStock(['code' => 'STK-002', 'name' => 'Casing HP']);

        Livewire::test(StockList::class)
            ->assertSee('Kartu Perdana')
            ->assertSee('Casing HP');
    }

    /** @test */
    public function stock_list_can_search_by_name()
    {
        $this->createStock(['code' => 'STK-001', 'name' => 'Kartu Perdana']);
        $this->createStock(['code' => 'STK-002', 'name' => 'Casing HP']);

        Livewire::test(StockList::class)
            ->set('search', 'Kartu')
            ->assertSee('Kartu Perdana')
            ->assertDontSee('Casing HP');
    }

    /** @test */
    public function stock_list_can_search_by_code()
    {
        $this->createStock(['code' => 'STK-001', 'name' => 'Kartu Perdana']);
        $this->createStock(['code' => 'STK-002', 'name' => 'Casing HP']);

        Livewire::test(StockList::class)
            ->set('search', 'STK-001')
            ->assertSee('Kartu Perdana')
            ->assertDontSee('Casing HP');
    }

    /** @test */
    public function stock_list_can_search_by_barcode()
    {
        $this->createStock(['code' => 'STK-001', 'name' => 'Kartu Perdana', 'barcode' => '1234567890']);
        $this->createStock(['code' => 'STK-002', 'name' => 'Casing HP', 'barcode' => '9876543210']);

        Livewire::test(StockList::class)
            ->set('search', '1234567890')
            ->assertSee('Kartu Perdana')
            ->assertDontSee('Casing HP');
    }

    /** @test */
    public function stock_list_can_filter_by_unit()
    {
        $unit2 = BusinessUnit::withoutEvents(function () {
            return BusinessUnit::create(['code' => 'UNT-002', 'name' => 'Unit 2', 'is_active' => true]);
        });
        $cat2 = StockCategory::withoutEvents(function () use ($unit2) {
            return StockCategory::create([
                'business_unit_id' => $unit2->id,
                'code' => 'CAT-002',
                'name' => 'Kategori 2',
                'type' => 'barang',
                'is_active' => true,
            ]);
        });
        $group2 = CategoryGroup::withoutEvents(function () use ($unit2, $cat2) {
            return CategoryGroup::create([
                'business_unit_id' => $unit2->id,
                'stock_category_id' => $cat2->id,
                'code' => 'GRP-002',
                'name' => 'Grup 2',
                'is_active' => true,
            ]);
        });
        $measure2 = UnitOfMeasure::withoutEvents(function () use ($unit2) {
            return UnitOfMeasure::create([
                'business_unit_id' => $unit2->id,
                'code' => 'KG',
                'name' => 'Kilogram',
                'is_active' => true,
            ]);
        });

        $this->createStock(['code' => 'STK-001', 'name' => 'Stok Unit 1']);
        Stock::withoutEvents(function () use ($unit2, $group2, $measure2) {
            return Stock::create([
                'business_unit_id' => $unit2->id,
                'category_group_id' => $group2->id,
                'unit_of_measure_id' => $measure2->id,
                'code' => 'STK-002',
                'name' => 'Stok Unit 2',
                'buy_price' => 5000,
                'sell_price' => 7000,
                'min_stock' => 3,
                'current_stock' => 8,
                'is_active' => true,
            ]);
        });

        Livewire::test(StockList::class)
            ->set('filterUnit', $this->unit->id)
            ->assertSee('Stok Unit 1')
            ->assertDontSee('Stok Unit 2');
    }

    /** @test */
    public function stock_list_can_filter_by_category_group()
    {
        $group2 = CategoryGroup::withoutEvents(function () {
            return CategoryGroup::create([
                'business_unit_id' => $this->unit->id,
                'stock_category_id' => $this->category->id,
                'code' => 'GRP-002',
                'name' => 'Grup 2',
                'is_active' => true,
            ]);
        });

        $this->createStock(['code' => 'STK-001', 'name' => 'Stok Grup 1', 'category_group_id' => $this->group->id]);
        $this->createStock(['code' => 'STK-002', 'name' => 'Stok Grup 2', 'category_group_id' => $group2->id]);

        Livewire::test(StockList::class)
            ->set('filterCategory', $this->group->id)
            ->assertSee('Stok Grup 1')
            ->assertDontSee('Stok Grup 2');
    }

    /** @test */
    public function stock_list_can_filter_by_status()
    {
        $this->createStock(['code' => 'STK-001', 'name' => 'Active Stock', 'is_active' => true]);
        $this->createStock(['code' => 'STK-002', 'name' => 'Inactive Stock', 'is_active' => false]);

        Livewire::test(StockList::class)
            ->set('filterStatus', '1')
            ->assertSee('Active Stock')
            ->assertDontSee('Inactive Stock');

        Livewire::test(StockList::class)
            ->set('filterStatus', '0')
            ->assertSee('Inactive Stock')
            ->assertDontSee('Active Stock');
    }

    /** @test */
    public function stock_list_can_sort()
    {
        $this->createStock(['code' => 'STK-002', 'name' => 'Beta']);
        $this->createStock(['code' => 'STK-001', 'name' => 'Alpha']);

        Livewire::test(StockList::class)
            ->call('sortBy', 'name')
            ->assertSeeInOrder(['Alpha', 'Beta']);
    }

    /** @test */
    public function stock_list_can_toggle_status()
    {
        $stock = $this->createStock();

        Livewire::test(StockList::class)
            ->call('toggleStatus', $stock->id)
            ->assertDispatched('alert');

        $this->assertFalse($stock->fresh()->is_active);
    }

    /** @test */
    public function stock_list_can_delete_stock()
    {
        $stock = $this->createStock();

        Livewire::test(StockList::class)
            ->call('deleteStock', $stock->id)
            ->assertDispatched('alert');

        $this->assertSoftDeleted('stocks', ['id' => $stock->id]);
    }

    // ==================== FORM COMPONENT TESTS ====================

    /** @test */
    public function stock_form_renders_successfully()
    {
        Livewire::test(StockForm::class)
            ->assertStatus(200);
    }

    /** @test */
    public function stock_form_can_open_modal()
    {
        Livewire::test(StockForm::class)
            ->call('openStockModal')
            ->assertSet('showModal', true)
            ->assertSet('isEditing', false);
    }

    /** @test */
    public function stock_form_can_create_stock()
    {
        Livewire::test(StockForm::class)
            ->call('openStockModal')
            ->set('business_unit_id', $this->unit->id)
            ->set('category_group_id', $this->group->id)
            ->set('unit_of_measure_id', $this->measure->id)
            ->set('code', 'STK-NEW')
            ->set('name', 'Stok Baru')
            ->set('buy_price', 10000)
            ->set('sell_price', 15000)
            ->set('min_stock', 5)
            ->call('save')
            ->assertDispatched('alert', type: 'success')
            ->assertDispatched('refreshStockList')
            ->assertSet('showModal', false);

        $this->assertDatabaseHas('stocks', [
            'business_unit_id' => $this->unit->id,
            'code' => 'STK-NEW',
            'name' => 'Stok Baru',
            'current_stock' => 0,
        ]);
    }

    /** @test */
    public function stock_form_can_create_with_barcode()
    {
        Livewire::test(StockForm::class)
            ->call('openStockModal')
            ->set('business_unit_id', $this->unit->id)
            ->set('category_group_id', $this->group->id)
            ->set('unit_of_measure_id', $this->measure->id)
            ->set('code', 'STK-BC')
            ->set('name', 'Stok Barcode')
            ->set('barcode', '1234567890')
            ->set('buy_price', 5000)
            ->set('sell_price', 7500)
            ->set('min_stock', 3)
            ->call('save')
            ->assertDispatched('alert', type: 'success');

        $this->assertDatabaseHas('stocks', [
            'code' => 'STK-BC',
            'barcode' => '1234567890',
        ]);
    }

    /** @test */
    public function stock_form_can_edit_stock()
    {
        $stock = $this->createStock();

        Livewire::test(StockForm::class)
            ->call('editStock', $stock->id)
            ->assertSet('showModal', true)
            ->assertSet('isEditing', true)
            ->assertSet('code', 'STK-001')
            ->set('name', 'Updated Stock')
            ->set('sell_price', 20000)
            ->call('save')
            ->assertDispatched('alert', type: 'success');

        $this->assertDatabaseHas('stocks', [
            'id' => $stock->id,
            'name' => 'Updated Stock',
            'sell_price' => 20000,
        ]);
    }

    /** @test */
    public function stock_form_validates_required_fields()
    {
        Livewire::test(StockForm::class)
            ->call('openStockModal')
            ->set('business_unit_id', '')
            ->set('category_group_id', '')
            ->set('unit_of_measure_id', '')
            ->set('code', '')
            ->set('name', '')
            ->call('save')
            ->assertHasErrors(['business_unit_id', 'category_group_id', 'unit_of_measure_id', 'code', 'name']);
    }

    /** @test */
    public function stock_form_validates_unique_code_per_unit()
    {
        $this->createStock(['code' => 'STK-001']);

        Livewire::test(StockForm::class)
            ->call('openStockModal')
            ->set('business_unit_id', $this->unit->id)
            ->set('category_group_id', $this->group->id)
            ->set('unit_of_measure_id', $this->measure->id)
            ->set('code', 'STK-001')
            ->set('name', 'Duplicate')
            ->set('buy_price', 1000)
            ->set('sell_price', 2000)
            ->set('min_stock', 1)
            ->call('save')
            ->assertHasErrors(['code']);
    }

    /** @test */
    public function stock_form_allows_same_code_in_different_units()
    {
        $this->createStock(['code' => 'STK-001']);

        $unit2 = BusinessUnit::withoutEvents(function () {
            return BusinessUnit::create(['code' => 'UNT-002', 'name' => 'Unit 2', 'is_active' => true]);
        });
        $cat2 = StockCategory::withoutEvents(function () use ($unit2) {
            return StockCategory::create([
                'business_unit_id' => $unit2->id,
                'code' => 'CAT-002',
                'name' => 'Kategori 2',
                'type' => 'barang',
                'is_active' => true,
            ]);
        });
        $group2 = CategoryGroup::withoutEvents(function () use ($unit2, $cat2) {
            return CategoryGroup::create([
                'business_unit_id' => $unit2->id,
                'stock_category_id' => $cat2->id,
                'code' => 'GRP-002',
                'name' => 'Grup 2',
                'is_active' => true,
            ]);
        });
        $measure2 = UnitOfMeasure::withoutEvents(function () use ($unit2) {
            return UnitOfMeasure::create([
                'business_unit_id' => $unit2->id,
                'code' => 'KG',
                'name' => 'Kilogram',
                'is_active' => true,
            ]);
        });

        Livewire::test(StockForm::class)
            ->call('openStockModal')
            ->set('business_unit_id', $unit2->id)
            ->set('category_group_id', $group2->id)
            ->set('unit_of_measure_id', $measure2->id)
            ->set('code', 'STK-001')
            ->set('name', 'Same Code Different Unit')
            ->set('buy_price', 1000)
            ->set('sell_price', 2000)
            ->set('min_stock', 1)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('stocks', 2);
    }

    /** @test */
    public function stock_form_resets_dependent_fields_when_unit_changes()
    {
        Livewire::test(StockForm::class)
            ->call('openStockModal')
            ->set('business_unit_id', $this->unit->id)
            ->set('category_group_id', $this->group->id)
            ->set('unit_of_measure_id', $this->measure->id)
            ->set('business_unit_id', 999)
            ->assertSet('category_group_id', '')
            ->assertSet('unit_of_measure_id', '');
    }

    /** @test */
    public function stock_form_can_close_modal()
    {
        Livewire::test(StockForm::class)
            ->call('openStockModal')
            ->assertSet('showModal', true)
            ->call('closeModal')
            ->assertSet('showModal', false);
    }

    /** @test */
    public function stock_form_new_stock_starts_with_zero_current_stock()
    {
        Livewire::test(StockForm::class)
            ->call('openStockModal')
            ->set('business_unit_id', $this->unit->id)
            ->set('category_group_id', $this->group->id)
            ->set('unit_of_measure_id', $this->measure->id)
            ->set('code', 'STK-ZERO')
            ->set('name', 'Zero Stock')
            ->set('buy_price', 1000)
            ->set('sell_price', 2000)
            ->set('min_stock', 5)
            ->call('save')
            ->assertDispatched('alert', type: 'success');

        $stock = Stock::where('code', 'STK-ZERO')->first();
        $this->assertEquals(0, $stock->current_stock);
    }

    // ==================== MODEL TESTS ====================

    /** @test */
    public function stock_belongs_to_business_unit()
    {
        $stock = $this->createStock();
        $this->assertInstanceOf(BusinessUnit::class, $stock->businessUnit);
    }

    /** @test */
    public function stock_belongs_to_category_group()
    {
        $stock = $this->createStock();
        $this->assertInstanceOf(CategoryGroup::class, $stock->categoryGroup);
    }

    /** @test */
    public function stock_belongs_to_unit_of_measure()
    {
        $stock = $this->createStock();
        $this->assertInstanceOf(UnitOfMeasure::class, $stock->unitOfMeasure);
    }

    /** @test */
    public function stock_is_low_stock_when_current_below_min()
    {
        $stock = $this->createStock(['current_stock' => 3, 'min_stock' => 5]);
        $this->assertTrue($stock->isLowStock());
    }

    /** @test */
    public function stock_is_not_low_stock_when_current_above_min()
    {
        $stock = $this->createStock(['current_stock' => 10, 'min_stock' => 5]);
        $this->assertFalse($stock->isLowStock());
    }

    /** @test */
    public function stock_is_low_stock_when_current_equals_min()
    {
        $stock = $this->createStock(['current_stock' => 5, 'min_stock' => 5]);
        $this->assertTrue($stock->isLowStock());
    }

    /** @test */
    public function stock_active_scope_works()
    {
        $this->createStock(['code' => 'STK-001', 'is_active' => true]);
        $this->createStock(['code' => 'STK-002', 'is_active' => false]);

        $this->assertEquals(1, Stock::active()->count());
    }

    /** @test */
    public function stock_low_stock_scope_works()
    {
        $this->createStock(['code' => 'STK-001', 'current_stock' => 3, 'min_stock' => 5]);
        $this->createStock(['code' => 'STK-002', 'current_stock' => 10, 'min_stock' => 5]);

        $this->assertEquals(1, Stock::lowStock()->count());
    }

    /** @test */
    public function stock_gets_type_from_category_group()
    {
        $stock = $this->createStock();
        $this->assertEquals('barang', $stock->type);
    }

    // ==================== RELATIONSHIP CHAIN TESTS ====================

    /** @test */
    public function business_unit_has_many_stocks()
    {
        $this->createStock(['code' => 'STK-001']);
        $this->createStock(['code' => 'STK-002']);

        $this->assertCount(2, $this->unit->stocks);
    }

    /** @test */
    public function business_unit_has_many_stock_categories()
    {
        $this->assertCount(1, $this->unit->stockCategories);
    }

    /** @test */
    public function business_unit_has_many_category_groups()
    {
        $this->assertCount(1, $this->unit->categoryGroups);
    }

    /** @test */
    public function business_unit_has_many_unit_of_measures()
    {
        $this->assertCount(1, $this->unit->unitOfMeasures);
    }

    // ==================== BUSINESS UNIT SCOPING TESTS ====================

    /** @test */
    public function non_superadmin_list_only_sees_own_unit_stocks()
    {
        $unit2 = BusinessUnit::withoutEvents(fn() => BusinessUnit::create(['code' => 'UNT-002', 'name' => 'Unit 2', 'is_active' => true]));
        $cat2 = StockCategory::withoutEvents(fn() => StockCategory::create(['business_unit_id' => $unit2->id, 'code' => 'CAT-002', 'name' => 'Cat 2', 'type' => 'barang', 'is_active' => true]));
        $grp2 = CategoryGroup::withoutEvents(fn() => CategoryGroup::create(['business_unit_id' => $unit2->id, 'stock_category_id' => $cat2->id, 'code' => 'GRP-002', 'name' => 'Grp 2', 'is_active' => true]));
        $msr2 = UnitOfMeasure::withoutEvents(fn() => UnitOfMeasure::create(['business_unit_id' => $unit2->id, 'code' => 'KG', 'name' => 'Kilogram', 'is_active' => true]));

        $this->createStock(['code' => 'STK-001', 'name' => 'Stok Unit 1']);
        Stock::withoutEvents(fn() => Stock::create([
            'business_unit_id' => $unit2->id, 'category_group_id' => $grp2->id, 'unit_of_measure_id' => $msr2->id,
            'code' => 'STK-002', 'name' => 'Stok Unit 2', 'buy_price' => 100, 'sell_price' => 200, 'min_stock' => 0, 'current_stock' => 0, 'is_active' => true,
        ]));

        $regularUser = User::withoutEvents(fn() => User::factory()->create(['business_unit_id' => $this->unit->id]));
        $this->actingAs($regularUser);

        Livewire::test(StockList::class)
            ->assertSee('Stok Unit 1')
            ->assertDontSee('Stok Unit 2');
    }

    /** @test */
    public function non_superadmin_form_auto_fills_business_unit_id()
    {
        $regularUser = User::withoutEvents(fn() => User::factory()->create(['business_unit_id' => $this->unit->id]));
        $this->actingAs($regularUser);

        Livewire::test(StockForm::class)
            ->call('openStockModal')
            ->assertSet('business_unit_id', $this->unit->id);
    }

    /** @test */
    public function non_superadmin_save_uses_own_business_unit_id()
    {
        $regularUser = User::withoutEvents(fn() => User::factory()->create(['business_unit_id' => $this->unit->id]));
        $this->actingAs($regularUser);

        Livewire::test(StockForm::class)
            ->call('openStockModal')
            ->set('category_group_id', $this->group->id)
            ->set('unit_of_measure_id', $this->measure->id)
            ->set('code', 'STK-AUTO')
            ->set('name', 'Auto Unit Stock')
            ->set('buy_price', 1000)
            ->set('sell_price', 2000)
            ->set('min_stock', 5)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('stocks', [
            'code' => 'STK-AUTO',
            'business_unit_id' => $this->unit->id,
        ]);
    }
}
