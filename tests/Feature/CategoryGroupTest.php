<?php

namespace Tests\Feature;

use App\Livewire\StockManagement\CategoryGroupForm;
use App\Livewire\StockManagement\CategoryGroupList;
use App\Models\BusinessUnit;
use App\Models\CategoryGroup;
use App\Models\COA;
use App\Models\Stock;
use App\Models\StockCategory;
use App\Models\UnitOfMeasure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CategoryGroupTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected BusinessUnit $unit;
    protected StockCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::withoutEvents(function () {
            return User::factory()->create();
        });
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
    }

    protected function createGroup(array $overrides = []): CategoryGroup
    {
        return CategoryGroup::withoutEvents(function () use ($overrides) {
            return CategoryGroup::create(array_merge([
                'business_unit_id' => $this->unit->id,
                'stock_category_id' => $this->category->id,
                'code' => 'GRP-001',
                'name' => 'Grup Test',
                'is_active' => true,
            ], $overrides));
        });
    }

    protected function createCoa(string $code, string $type, bool $isLeaf = true): COA
    {
        return COA::withoutEvents(function () use ($code, $type, $isLeaf) {
            return COA::create([
                'code' => $code,
                'name' => "COA {$code}",
                'type' => $type,
                'normal_balance' => in_array($type, ['aktiva', 'beban']) ? 'debit' : 'credit',
                'is_active' => true,
                'is_leaf_account' => $isLeaf,
                'level' => 1,
            ]);
        });
    }

    // ==================== PAGE ACCESS TESTS ====================

    /** @test */
    public function category_group_index_page_is_accessible()
    {
        $response = $this->get(route('category-group.index'));
        $response->assertStatus(200);
        $response->assertSee('Grup Kategori');
    }

    /** @test */
    public function guest_cannot_access_category_group_page()
    {
        auth()->logout();
        $this->get(route('category-group.index'))->assertRedirect(route('login'));
    }

    // ==================== LIST COMPONENT TESTS ====================

    /** @test */
    public function category_group_list_renders_successfully()
    {
        Livewire::test(CategoryGroupList::class)
            ->assertStatus(200);
    }

    /** @test */
    public function category_group_list_shows_groups()
    {
        $this->createGroup(['code' => 'GRP-001', 'name' => 'Aksesoris']);
        $this->createGroup(['code' => 'GRP-002', 'name' => 'Kartu']);

        Livewire::test(CategoryGroupList::class)
            ->assertSee('Aksesoris')
            ->assertSee('Kartu');
    }

    /** @test */
    public function category_group_list_can_search()
    {
        $this->createGroup(['code' => 'GRP-001', 'name' => 'Aksesoris']);
        $this->createGroup(['code' => 'GRP-002', 'name' => 'Kartu']);

        Livewire::test(CategoryGroupList::class)
            ->set('search', 'Aksesoris')
            ->assertSee('Aksesoris')
            ->assertDontSee('Kartu');
    }

    /** @test */
    public function category_group_list_can_filter_by_unit()
    {
        $unit2 = BusinessUnit::withoutEvents(function () {
            return BusinessUnit::create(['code' => 'UNT-002', 'name' => 'Unit 2', 'is_active' => true]);
        });
        $cat2 = StockCategory::withoutEvents(function () use ($unit2) {
            return StockCategory::create([
                'business_unit_id' => $unit2->id,
                'code' => 'CAT-002',
                'name' => 'Kategori 2',
                'type' => 'jasa',
                'is_active' => true,
            ]);
        });

        $this->createGroup(['code' => 'GRP-001', 'name' => 'Grup Unit 1']);
        CategoryGroup::withoutEvents(function () use ($unit2, $cat2) {
            return CategoryGroup::create([
                'business_unit_id' => $unit2->id,
                'stock_category_id' => $cat2->id,
                'code' => 'GRP-002',
                'name' => 'Grup Unit 2',
                'is_active' => true,
            ]);
        });

        Livewire::test(CategoryGroupList::class)
            ->set('filterUnit', $this->unit->id)
            ->assertSee('Grup Unit 1')
            ->assertDontSee('Grup Unit 2');
    }

    /** @test */
    public function category_group_list_can_filter_by_category()
    {
        $cat2 = StockCategory::withoutEvents(function () {
            return StockCategory::create([
                'business_unit_id' => $this->unit->id,
                'code' => 'CAT-002',
                'name' => 'Kategori Jasa',
                'type' => 'jasa',
                'is_active' => true,
            ]);
        });

        $this->createGroup(['code' => 'GRP-001', 'name' => 'Grup Barang', 'stock_category_id' => $this->category->id]);
        $this->createGroup(['code' => 'GRP-002', 'name' => 'Grup Jasa', 'stock_category_id' => $cat2->id]);

        Livewire::test(CategoryGroupList::class)
            ->set('filterCategory', $this->category->id)
            ->assertSee('Grup Barang')
            ->assertDontSee('Grup Jasa');
    }

    /** @test */
    public function category_group_list_can_filter_by_status()
    {
        $this->createGroup(['code' => 'GRP-001', 'name' => 'Active Group', 'is_active' => true]);
        $this->createGroup(['code' => 'GRP-002', 'name' => 'Inactive Group', 'is_active' => false]);

        Livewire::test(CategoryGroupList::class)
            ->set('filterStatus', '1')
            ->assertSee('Active Group')
            ->assertDontSee('Inactive Group');
    }

    /** @test */
    public function category_group_list_can_sort()
    {
        $this->createGroup(['code' => 'GRP-002', 'name' => 'Beta']);
        $this->createGroup(['code' => 'GRP-001', 'name' => 'Alpha']);

        Livewire::test(CategoryGroupList::class)
            ->call('sortBy', 'name')
            ->assertSeeInOrder(['Alpha', 'Beta']);
    }

    /** @test */
    public function category_group_list_can_toggle_status()
    {
        $group = $this->createGroup();

        Livewire::test(CategoryGroupList::class)
            ->call('toggleStatus', $group->id)
            ->assertDispatched('alert');

        $this->assertFalse($group->fresh()->is_active);
    }

    /** @test */
    public function category_group_list_can_delete_group_without_stocks()
    {
        $group = $this->createGroup();

        Livewire::test(CategoryGroupList::class)
            ->call('deleteGroup', $group->id)
            ->assertDispatched('alert');

        $this->assertSoftDeleted('category_groups', ['id' => $group->id]);
    }

    /** @test */
    public function category_group_list_prevents_deleting_group_with_stocks()
    {
        $group = $this->createGroup();

        $measure = UnitOfMeasure::withoutEvents(function () {
            return UnitOfMeasure::create([
                'business_unit_id' => $this->unit->id,
                'code' => 'PCS',
                'name' => 'Pieces',
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

        Livewire::test(CategoryGroupList::class)
            ->call('deleteGroup', $group->id)
            ->assertDispatched('alert', type: 'error');

        $this->assertDatabaseHas('category_groups', ['id' => $group->id, 'deleted_at' => null]);
    }

    // ==================== FORM COMPONENT TESTS ====================

    /** @test */
    public function category_group_form_renders_successfully()
    {
        Livewire::test(CategoryGroupForm::class)
            ->assertStatus(200);
    }

    /** @test */
    public function category_group_form_can_open_modal()
    {
        Livewire::test(CategoryGroupForm::class)
            ->call('openCategoryGroupModal')
            ->assertSet('showModal', true)
            ->assertSet('isEditing', false);
    }

    /** @test */
    public function category_group_form_can_create_group()
    {
        Livewire::test(CategoryGroupForm::class)
            ->call('openCategoryGroupModal')
            ->set('business_unit_id', $this->unit->id)
            ->set('stock_category_id', $this->category->id)
            ->set('code', 'GRP-NEW')
            ->set('name', 'Grup Baru')
            ->call('save')
            ->assertDispatched('alert', type: 'success')
            ->assertDispatched('refreshCategoryGroupList')
            ->assertSet('showModal', false);

        $this->assertDatabaseHas('category_groups', [
            'business_unit_id' => $this->unit->id,
            'stock_category_id' => $this->category->id,
            'code' => 'GRP-NEW',
            'name' => 'Grup Baru',
        ]);
    }

    /** @test */
    public function category_group_form_can_create_with_coa_mappings()
    {
        $coaInventory = $this->createCoa('1100', 'aktiva');
        $coaRevenue = $this->createCoa('4100', 'pendapatan');
        $coaExpense = $this->createCoa('5100', 'beban');

        Livewire::test(CategoryGroupForm::class)
            ->call('openCategoryGroupModal')
            ->set('business_unit_id', $this->unit->id)
            ->set('stock_category_id', $this->category->id)
            ->set('code', 'GRP-COA')
            ->set('name', 'Grup dengan Akun')
            ->set('coa_inventory_id', $coaInventory->id)
            ->set('coa_revenue_id', $coaRevenue->id)
            ->set('coa_expense_id', $coaExpense->id)
            ->call('save')
            ->assertDispatched('alert', type: 'success');

        $this->assertDatabaseHas('category_groups', [
            'code' => 'GRP-COA',
            'coa_inventory_id' => $coaInventory->id,
            'coa_revenue_id' => $coaRevenue->id,
            'coa_expense_id' => $coaExpense->id,
        ]);
    }

    /** @test */
    public function category_group_form_can_edit_group()
    {
        $group = $this->createGroup();

        Livewire::test(CategoryGroupForm::class)
            ->call('editCategoryGroup', $group->id)
            ->assertSet('showModal', true)
            ->assertSet('isEditing', true)
            ->assertSet('code', 'GRP-001')
            ->set('name', 'Updated Grup')
            ->call('save')
            ->assertDispatched('alert', type: 'success');

        $this->assertDatabaseHas('category_groups', [
            'id' => $group->id,
            'name' => 'Updated Grup',
        ]);
    }

    /** @test */
    public function category_group_form_validates_required_fields()
    {
        Livewire::test(CategoryGroupForm::class)
            ->call('openCategoryGroupModal')
            ->set('business_unit_id', '')
            ->set('stock_category_id', '')
            ->set('code', '')
            ->set('name', '')
            ->call('save')
            ->assertHasErrors(['business_unit_id', 'stock_category_id', 'code', 'name']);
    }

    /** @test */
    public function category_group_form_validates_unique_code_per_unit()
    {
        $this->createGroup(['code' => 'GRP-001']);

        Livewire::test(CategoryGroupForm::class)
            ->call('openCategoryGroupModal')
            ->set('business_unit_id', $this->unit->id)
            ->set('stock_category_id', $this->category->id)
            ->set('code', 'GRP-001')
            ->set('name', 'Duplicate')
            ->call('save')
            ->assertHasErrors(['code']);
    }

    /** @test */
    public function category_group_form_resets_category_when_unit_changes()
    {
        Livewire::test(CategoryGroupForm::class)
            ->call('openCategoryGroupModal')
            ->set('business_unit_id', $this->unit->id)
            ->set('stock_category_id', $this->category->id)
            ->set('business_unit_id', 999)
            ->assertSet('stock_category_id', '');
    }

    /** @test */
    public function category_group_form_can_close_modal()
    {
        Livewire::test(CategoryGroupForm::class)
            ->call('openCategoryGroupModal')
            ->assertSet('showModal', true)
            ->call('closeModal')
            ->assertSet('showModal', false);
    }

    // ==================== MODEL TESTS ====================

    /** @test */
    public function category_group_belongs_to_business_unit()
    {
        $group = $this->createGroup();
        $this->assertInstanceOf(BusinessUnit::class, $group->businessUnit);
    }

    /** @test */
    public function category_group_belongs_to_stock_category()
    {
        $group = $this->createGroup();
        $this->assertInstanceOf(StockCategory::class, $group->stockCategory);
    }

    /** @test */
    public function category_group_has_coa_relationships()
    {
        $coaInventory = $this->createCoa('1100', 'aktiva');
        $coaRevenue = $this->createCoa('4100', 'pendapatan');
        $coaExpense = $this->createCoa('5100', 'beban');

        $group = $this->createGroup([
            'coa_inventory_id' => $coaInventory->id,
            'coa_revenue_id' => $coaRevenue->id,
            'coa_expense_id' => $coaExpense->id,
        ]);

        $this->assertInstanceOf(COA::class, $group->coaInventory);
        $this->assertInstanceOf(COA::class, $group->coaRevenue);
        $this->assertInstanceOf(COA::class, $group->coaExpense);
    }

    /** @test */
    public function category_group_active_scope_works()
    {
        $this->createGroup(['code' => 'GRP-001', 'is_active' => true]);
        $this->createGroup(['code' => 'GRP-002', 'is_active' => false]);

        $this->assertEquals(1, CategoryGroup::active()->count());
    }
}
