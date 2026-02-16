<?php

namespace Tests\Feature;

use App\Livewire\StockManagement\StockCategoryForm;
use App\Livewire\StockManagement\StockCategoryList;
use App\Models\BusinessUnit;
use App\Models\CategoryGroup;
use App\Models\StockCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StockCategoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected BusinessUnit $unit;

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
    }

    protected function createCategory(array $overrides = []): StockCategory
    {
        return StockCategory::withoutEvents(function () use ($overrides) {
            return StockCategory::create(array_merge([
                'business_unit_id' => $this->unit->id,
                'code' => 'CAT-001',
                'name' => 'Kategori Test',
                'type' => 'barang',
                'is_active' => true,
            ], $overrides));
        });
    }

    // ==================== PAGE ACCESS TESTS ====================

    /** @test */
    public function stock_category_index_page_is_accessible()
    {
        $response = $this->get(route('stock-category.index'));
        $response->assertStatus(200);
        $response->assertSee('Kategori Stok');
    }

    /** @test */
    public function guest_cannot_access_stock_category_page()
    {
        auth()->logout();
        $this->get(route('stock-category.index'))->assertRedirect(route('login'));
    }

    // ==================== LIST COMPONENT TESTS ====================

    /** @test */
    public function stock_category_list_renders_successfully()
    {
        Livewire::test(StockCategoryList::class)
            ->assertStatus(200);
    }

    /** @test */
    public function stock_category_list_shows_categories()
    {
        $this->createCategory(['code' => 'CAT-001', 'name' => 'Kategori Alpha']);
        $this->createCategory(['code' => 'CAT-002', 'name' => 'Kategori Beta']);

        Livewire::test(StockCategoryList::class)
            ->assertSee('Kategori Alpha')
            ->assertSee('Kategori Beta');
    }

    /** @test */
    public function stock_category_list_can_search()
    {
        $this->createCategory(['code' => 'CAT-001', 'name' => 'Kategori Alpha']);
        $this->createCategory(['code' => 'CAT-002', 'name' => 'Kategori Beta']);

        Livewire::test(StockCategoryList::class)
            ->set('search', 'Alpha')
            ->assertSee('Kategori Alpha')
            ->assertDontSee('Kategori Beta');
    }

    /** @test */
    public function stock_category_list_can_filter_by_unit()
    {
        $unit2 = BusinessUnit::withoutEvents(function () {
            return BusinessUnit::create(['code' => 'UNT-002', 'name' => 'Unit 2', 'is_active' => true]);
        });

        $this->createCategory(['code' => 'CAT-001', 'name' => 'Dari Unit 1']);
        StockCategory::withoutEvents(function () use ($unit2) {
            return StockCategory::create([
                'business_unit_id' => $unit2->id,
                'code' => 'CAT-002',
                'name' => 'Dari Unit 2',
                'type' => 'jasa',
                'is_active' => true,
            ]);
        });

        Livewire::test(StockCategoryList::class)
            ->set('filterUnit', $this->unit->id)
            ->assertSee('Dari Unit 1')
            ->assertDontSee('Dari Unit 2');
    }

    /** @test */
    public function stock_category_list_can_filter_by_type()
    {
        $this->createCategory(['code' => 'CAT-001', 'name' => 'Barang Satu', 'type' => 'barang']);
        $this->createCategory(['code' => 'CAT-002', 'name' => 'Jasa Satu', 'type' => 'jasa']);

        Livewire::test(StockCategoryList::class)
            ->set('filterType', 'barang')
            ->assertSee('Barang Satu')
            ->assertDontSee('Jasa Satu');
    }

    /** @test */
    public function stock_category_list_can_filter_by_status()
    {
        $this->createCategory(['code' => 'CAT-001', 'name' => 'Active Cat', 'is_active' => true]);
        $this->createCategory(['code' => 'CAT-002', 'name' => 'Inactive Cat', 'is_active' => false]);

        Livewire::test(StockCategoryList::class)
            ->set('filterStatus', '1')
            ->assertSee('Active Cat')
            ->assertDontSee('Inactive Cat');

        Livewire::test(StockCategoryList::class)
            ->set('filterStatus', '0')
            ->assertSee('Inactive Cat')
            ->assertDontSee('Active Cat');
    }

    /** @test */
    public function stock_category_list_can_sort()
    {
        $this->createCategory(['code' => 'CAT-002', 'name' => 'Beta']);
        $this->createCategory(['code' => 'CAT-001', 'name' => 'Alpha']);

        Livewire::test(StockCategoryList::class)
            ->call('sortBy', 'name')
            ->assertSeeInOrder(['Alpha', 'Beta']);
    }

    /** @test */
    public function stock_category_list_can_toggle_status()
    {
        $category = $this->createCategory();

        Livewire::test(StockCategoryList::class)
            ->call('toggleStatus', $category->id)
            ->assertDispatched('alert');

        $this->assertFalse($category->fresh()->is_active);
    }

    /** @test */
    public function stock_category_list_can_delete_category_without_groups()
    {
        $category = $this->createCategory();

        Livewire::test(StockCategoryList::class)
            ->call('deleteCategory', $category->id)
            ->assertDispatched('alert');

        $this->assertSoftDeleted('stock_categories', ['id' => $category->id]);
    }

    /** @test */
    public function stock_category_list_prevents_deleting_category_with_groups()
    {
        $category = $this->createCategory();

        CategoryGroup::withoutEvents(function () use ($category) {
            return CategoryGroup::create([
                'business_unit_id' => $this->unit->id,
                'stock_category_id' => $category->id,
                'code' => 'GRP-001',
                'name' => 'Group Test',
                'is_active' => true,
            ]);
        });

        Livewire::test(StockCategoryList::class)
            ->call('deleteCategory', $category->id)
            ->assertDispatched('alert', type: 'error');

        $this->assertDatabaseHas('stock_categories', ['id' => $category->id, 'deleted_at' => null]);
    }

    // ==================== FORM COMPONENT TESTS ====================

    /** @test */
    public function stock_category_form_renders_successfully()
    {
        Livewire::test(StockCategoryForm::class)
            ->assertStatus(200);
    }

    /** @test */
    public function stock_category_form_can_open_modal()
    {
        Livewire::test(StockCategoryForm::class)
            ->call('openStockCategoryModal')
            ->assertSet('showModal', true)
            ->assertSet('isEditing', false);
    }

    /** @test */
    public function stock_category_form_can_create_category()
    {
        Livewire::test(StockCategoryForm::class)
            ->call('openStockCategoryModal')
            ->set('business_unit_id', $this->unit->id)
            ->set('code', 'CAT-NEW')
            ->set('name', 'Kategori Baru')
            ->set('type', 'barang')
            ->call('save')
            ->assertDispatched('alert', type: 'success')
            ->assertDispatched('refreshStockCategoryList')
            ->assertSet('showModal', false);

        $this->assertDatabaseHas('stock_categories', [
            'business_unit_id' => $this->unit->id,
            'code' => 'CAT-NEW',
            'name' => 'Kategori Baru',
            'type' => 'barang',
        ]);
    }

    /** @test */
    public function stock_category_form_can_edit_category()
    {
        $category = $this->createCategory();

        Livewire::test(StockCategoryForm::class)
            ->call('editStockCategory', $category->id)
            ->assertSet('showModal', true)
            ->assertSet('isEditing', true)
            ->assertSet('code', 'CAT-001')
            ->set('name', 'Updated Name')
            ->call('save')
            ->assertDispatched('alert', type: 'success');

        $this->assertDatabaseHas('stock_categories', [
            'id' => $category->id,
            'name' => 'Updated Name',
        ]);
    }

    /** @test */
    public function stock_category_form_validates_required_fields()
    {
        Livewire::test(StockCategoryForm::class)
            ->call('openStockCategoryModal')
            ->set('business_unit_id', '')
            ->set('code', '')
            ->set('name', '')
            ->set('type', '')
            ->call('save')
            ->assertHasErrors(['business_unit_id', 'code', 'name', 'type']);
    }

    /** @test */
    public function stock_category_form_validates_unique_code_per_unit()
    {
        $this->createCategory(['code' => 'CAT-001']);

        Livewire::test(StockCategoryForm::class)
            ->call('openStockCategoryModal')
            ->set('business_unit_id', $this->unit->id)
            ->set('code', 'CAT-001')
            ->set('name', 'Duplicate Code')
            ->set('type', 'barang')
            ->call('save')
            ->assertHasErrors(['code']);
    }

    /** @test */
    public function stock_category_form_allows_same_code_in_different_units()
    {
        $this->createCategory(['code' => 'CAT-001']);

        $unit2 = BusinessUnit::withoutEvents(function () {
            return BusinessUnit::create(['code' => 'UNT-002', 'name' => 'Unit 2', 'is_active' => true]);
        });

        Livewire::test(StockCategoryForm::class)
            ->call('openStockCategoryModal')
            ->set('business_unit_id', $unit2->id)
            ->set('code', 'CAT-001')
            ->set('name', 'Same Code Different Unit')
            ->set('type', 'jasa')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('stock_categories', 2);
    }

    /** @test */
    public function stock_category_form_can_close_modal()
    {
        Livewire::test(StockCategoryForm::class)
            ->call('openStockCategoryModal')
            ->assertSet('showModal', true)
            ->call('closeModal')
            ->assertSet('showModal', false);
    }

    // ==================== MODEL TESTS ====================

    /** @test */
    public function stock_category_has_correct_types()
    {
        $types = StockCategory::getTypes();
        $this->assertEquals(['barang' => 'Barang', 'jasa' => 'Jasa', 'saldo' => 'Saldo'], $types);
    }

    /** @test */
    public function stock_category_belongs_to_business_unit()
    {
        $category = $this->createCategory();
        $this->assertInstanceOf(BusinessUnit::class, $category->businessUnit);
    }

    /** @test */
    public function stock_category_has_many_category_groups()
    {
        $category = $this->createCategory();
        $this->assertCount(0, $category->categoryGroups);
    }

    /** @test */
    public function stock_category_active_scope_works()
    {
        $this->createCategory(['code' => 'CAT-001', 'is_active' => true]);
        $this->createCategory(['code' => 'CAT-002', 'is_active' => false]);

        $this->assertEquals(1, StockCategory::active()->count());
    }

    /** @test */
    public function stock_category_by_type_scope_works()
    {
        $this->createCategory(['code' => 'CAT-001', 'type' => 'barang']);
        $this->createCategory(['code' => 'CAT-002', 'type' => 'jasa']);
        $this->createCategory(['code' => 'CAT-003', 'type' => 'saldo']);

        $this->assertEquals(1, StockCategory::byType('barang')->count());
        $this->assertEquals(1, StockCategory::byType('jasa')->count());
        $this->assertEquals(1, StockCategory::byType('saldo')->count());
    }
}
