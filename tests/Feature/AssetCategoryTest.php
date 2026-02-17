<?php

namespace Tests\Feature;

use App\Livewire\Asset\AssetCategoryForm;
use App\Livewire\Asset\AssetCategoryList;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\BusinessUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AssetCategoryTest extends TestCase
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

    protected function createCategory(array $overrides = []): AssetCategory
    {
        return AssetCategory::withoutEvents(function () use ($overrides) {
            return AssetCategory::create(array_merge([
                'business_unit_id' => $this->unit->id,
                'code' => 'AC-001',
                'name' => 'Peralatan Kantor',
                'useful_life_months' => 60,
                'depreciation_method' => 'straight_line',
                'is_active' => true,
            ], $overrides));
        });
    }

    // ==================== PAGE ACCESS TESTS ====================

    /** @test */
    public function asset_category_page_is_accessible()
    {
        $response = $this->get(route('asset-category.index'));
        $response->assertStatus(200);
        $response->assertSee('Kategori Aset');
    }

    /** @test */
    public function guest_cannot_access_asset_category_page()
    {
        auth()->logout();
        $this->get(route('asset-category.index'))->assertRedirect(route('login'));
    }

    // ==================== LIST COMPONENT TESTS ====================

    /** @test */
    public function asset_category_list_renders_successfully()
    {
        Livewire::test(AssetCategoryList::class)->assertStatus(200);
    }

    /** @test */
    public function asset_category_list_shows_categories()
    {
        $this->createCategory(['code' => 'AC-001', 'name' => 'Peralatan Kantor']);
        $this->createCategory(['code' => 'AC-002', 'name' => 'Kendaraan']);

        Livewire::test(AssetCategoryList::class)
            ->assertSee('Peralatan Kantor')
            ->assertSee('Kendaraan');
    }

    /** @test */
    public function asset_category_list_can_search()
    {
        $this->createCategory(['code' => 'AC-001', 'name' => 'Peralatan Kantor']);
        $this->createCategory(['code' => 'AC-002', 'name' => 'Kendaraan']);

        Livewire::test(AssetCategoryList::class)
            ->set('search', 'Kendaraan')
            ->assertSee('Kendaraan')
            ->assertDontSee('Peralatan Kantor');
    }

    /** @test */
    public function asset_category_list_can_filter_by_status()
    {
        $this->createCategory(['code' => 'AC-001', 'name' => 'Active Cat', 'is_active' => true]);
        $this->createCategory(['code' => 'AC-002', 'name' => 'Inactive Cat', 'is_active' => false]);

        Livewire::test(AssetCategoryList::class)
            ->set('filterStatus', '1')
            ->assertSee('Active Cat')
            ->assertDontSee('Inactive Cat');
    }

    /** @test */
    public function superadmin_can_filter_categories_by_unit()
    {
        $unit2 = BusinessUnit::withoutEvents(fn() =>
            BusinessUnit::create(['code' => 'UNT-002', 'name' => 'Unit 2', 'is_active' => true])
        );

        $this->createCategory(['code' => 'AC-001', 'name' => 'Cat Unit 1', 'business_unit_id' => $this->unit->id]);
        $this->createCategory(['code' => 'AC-002', 'name' => 'Cat Unit 2', 'business_unit_id' => $unit2->id]);

        Livewire::test(AssetCategoryList::class)
            ->set('filterUnit', $this->unit->id)
            ->assertSee('Cat Unit 1')
            ->assertDontSee('Cat Unit 2');
    }

    /** @test */
    public function asset_category_list_can_sort()
    {
        $this->createCategory(['code' => 'ZZZ', 'name' => 'Zebra']);
        $this->createCategory(['code' => 'AAA', 'name' => 'Alpha']);

        Livewire::test(AssetCategoryList::class)
            ->call('sortBy', 'name')
            ->assertSeeInOrder(['Alpha', 'Zebra']);
    }

    /** @test */
    public function asset_category_list_can_toggle_status()
    {
        $category = $this->createCategory();

        Livewire::test(AssetCategoryList::class)
            ->call('toggleStatus', $category->id)
            ->assertDispatched('alert');

        $this->assertFalse($category->fresh()->is_active);
    }

    /** @test */
    public function asset_category_can_be_deleted()
    {
        $category = $this->createCategory();

        Livewire::test(AssetCategoryList::class)
            ->call('deleteCategory', $category->id)
            ->assertDispatched('alert');

        $this->assertSoftDeleted('asset_categories', ['id' => $category->id]);
    }

    /** @test */
    public function asset_category_with_assets_cannot_be_deleted()
    {
        $category = $this->createCategory();

        Asset::withoutEvents(fn() => Asset::create([
            'business_unit_id' => $this->unit->id,
            'asset_category_id' => $category->id,
            'code' => 'AST-001',
            'name' => 'Test Asset',
            'acquisition_date' => now(),
            'acquisition_cost' => 1000000,
            'useful_life_months' => 60,
            'salvage_value' => 0,
            'depreciation_method' => 'straight_line',
            'condition' => 'good',
            'status' => 'active',
        ]));

        Livewire::test(AssetCategoryList::class)
            ->call('deleteCategory', $category->id)
            ->assertDispatched('alert', type: 'error');

        $this->assertDatabaseHas('asset_categories', ['id' => $category->id, 'deleted_at' => null]);
    }

    // ==================== FORM COMPONENT TESTS ====================

    /** @test */
    public function asset_category_form_renders_successfully()
    {
        Livewire::test(AssetCategoryForm::class)->assertStatus(200);
    }

    /** @test */
    public function asset_category_form_can_open_modal()
    {
        Livewire::test(AssetCategoryForm::class)
            ->call('openAssetCategoryModal')
            ->assertSet('showModal', true)
            ->assertSet('isEditing', false);
    }

    /** @test */
    public function asset_category_form_can_create_category()
    {
        Livewire::test(AssetCategoryForm::class)
            ->call('openAssetCategoryModal')
            ->set('business_unit_id', $this->unit->id)
            ->set('code', 'AC-NEW')
            ->set('name', 'Kategori Baru')
            ->set('useful_life_months', 48)
            ->set('depreciation_method', 'straight_line')
            ->call('save')
            ->assertDispatched('alert', type: 'success')
            ->assertDispatched('refreshAssetCategoryList')
            ->assertSet('showModal', false);

        $this->assertDatabaseHas('asset_categories', [
            'code' => 'AC-NEW',
            'name' => 'Kategori Baru',
            'useful_life_months' => 48,
        ]);
    }

    /** @test */
    public function asset_category_form_can_create_with_declining_balance()
    {
        Livewire::test(AssetCategoryForm::class)
            ->call('openAssetCategoryModal')
            ->set('business_unit_id', $this->unit->id)
            ->set('code', 'AC-DB')
            ->set('name', 'Declining Balance Cat')
            ->set('useful_life_months', 36)
            ->set('depreciation_method', 'declining_balance')
            ->call('save')
            ->assertDispatched('alert', type: 'success');

        $this->assertDatabaseHas('asset_categories', [
            'code' => 'AC-DB',
            'depreciation_method' => 'declining_balance',
        ]);
    }

    /** @test */
    public function asset_category_form_can_edit_category()
    {
        $category = $this->createCategory();

        Livewire::test(AssetCategoryForm::class)
            ->call('editAssetCategory', $category->id)
            ->assertSet('showModal', true)
            ->assertSet('isEditing', true)
            ->set('name', 'Updated Category')
            ->call('save')
            ->assertDispatched('alert', type: 'success');

        $this->assertDatabaseHas('asset_categories', ['id' => $category->id, 'name' => 'Updated Category']);
    }

    /** @test */
    public function asset_category_form_validates_required_fields()
    {
        Livewire::test(AssetCategoryForm::class)
            ->call('openAssetCategoryModal')
            ->set('code', '')
            ->set('name', '')
            ->set('business_unit_id', '')
            ->call('save')
            ->assertHasErrors(['code', 'name', 'business_unit_id']);
    }

    /** @test */
    public function asset_category_form_validates_unique_code_per_unit()
    {
        $this->createCategory(['code' => 'AC-001']);

        Livewire::test(AssetCategoryForm::class)
            ->call('openAssetCategoryModal')
            ->set('business_unit_id', $this->unit->id)
            ->set('code', 'AC-001')
            ->set('name', 'Duplicate')
            ->set('useful_life_months', 60)
            ->set('depreciation_method', 'straight_line')
            ->call('save')
            ->assertHasErrors(['code']);
    }

    /** @test */
    public function asset_category_form_validates_depreciation_method_enum()
    {
        Livewire::test(AssetCategoryForm::class)
            ->call('openAssetCategoryModal')
            ->set('business_unit_id', $this->unit->id)
            ->set('code', 'AC-001')
            ->set('name', 'Test')
            ->set('depreciation_method', 'invalid_method')
            ->call('save')
            ->assertHasErrors(['depreciation_method']);
    }

    /** @test */
    public function asset_category_form_can_close_modal()
    {
        Livewire::test(AssetCategoryForm::class)
            ->call('openAssetCategoryModal')
            ->assertSet('showModal', true)
            ->call('closeModal')
            ->assertSet('showModal', false);
    }

    // ==================== MODEL TESTS ====================

    /** @test */
    public function asset_category_has_depreciation_methods_constant()
    {
        $methods = AssetCategory::DEPRECIATION_METHODS;
        $this->assertArrayHasKey('straight_line', $methods);
        $this->assertArrayHasKey('declining_balance', $methods);
    }

    /** @test */
    public function asset_category_active_scope_works()
    {
        $this->createCategory(['code' => 'AC-001', 'is_active' => true]);
        $this->createCategory(['code' => 'AC-002', 'is_active' => false]);

        $this->assertEquals(1, AssetCategory::active()->count());
    }

    /** @test */
    public function asset_category_by_business_unit_scope_works()
    {
        $unit2 = BusinessUnit::withoutEvents(fn() =>
            BusinessUnit::create(['code' => 'UNT-002', 'name' => 'Unit 2', 'is_active' => true])
        );

        $this->createCategory(['code' => 'AC-001', 'business_unit_id' => $this->unit->id]);
        $this->createCategory(['code' => 'AC-002', 'business_unit_id' => $unit2->id]);

        $this->assertEquals(1, AssetCategory::byBusinessUnit($this->unit->id)->count());
    }

    /** @test */
    public function asset_category_belongs_to_business_unit()
    {
        $category = $this->createCategory();
        $this->assertInstanceOf(BusinessUnit::class, $category->businessUnit);
    }

    /** @test */
    public function asset_category_has_many_assets()
    {
        $category = $this->createCategory();
        $this->assertCount(0, $category->assets);

        Asset::withoutEvents(fn() => Asset::create([
            'business_unit_id' => $this->unit->id,
            'asset_category_id' => $category->id,
            'code' => 'AST-001',
            'name' => 'Test Asset',
            'acquisition_date' => now(),
            'acquisition_cost' => 1000000,
            'useful_life_months' => 60,
            'salvage_value' => 0,
            'depreciation_method' => 'straight_line',
            'condition' => 'good',
            'status' => 'active',
        ]));

        $this->assertCount(1, $category->fresh()->assets);
    }

    // ==================== NON-SUPERADMIN TESTS ====================

    /** @test */
    public function non_superadmin_only_sees_own_unit_categories()
    {
        $unit2 = BusinessUnit::withoutEvents(fn() =>
            BusinessUnit::create(['code' => 'UNT-002', 'name' => 'Unit 2', 'is_active' => true])
        );

        $this->createCategory(['code' => 'AC-001', 'name' => 'My Cat', 'business_unit_id' => $this->unit->id]);
        $this->createCategory(['code' => 'AC-002', 'name' => 'Other Cat', 'business_unit_id' => $unit2->id]);

        $regularUser = User::withoutEvents(fn() => User::factory()->create(['business_unit_id' => $this->unit->id]));
        $this->actingAs($regularUser);

        Livewire::test(AssetCategoryList::class)
            ->assertSee('My Cat')
            ->assertDontSee('Other Cat');
    }
}
