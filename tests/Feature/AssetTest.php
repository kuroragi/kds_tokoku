<?php

namespace Tests\Feature;

use App\Livewire\Asset\AssetForm;
use App\Livewire\Asset\AssetList;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetDepreciation;
use App\Models\BusinessUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AssetTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected BusinessUnit $unit;
    protected AssetCategory $category;

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

        $this->category = AssetCategory::withoutEvents(fn() => AssetCategory::create([
            'business_unit_id' => $this->unit->id,
            'code' => 'AC-001',
            'name' => 'Peralatan Kantor',
            'useful_life_months' => 60,
            'depreciation_method' => 'straight_line',
            'is_active' => true,
        ]));
    }

    protected function createAsset(array $overrides = []): Asset
    {
        return Asset::withoutEvents(function () use ($overrides) {
            return Asset::create(array_merge([
                'business_unit_id' => $this->unit->id,
                'asset_category_id' => $this->category->id,
                'code' => 'AST-001',
                'name' => 'Laptop Asus',
                'acquisition_date' => '2025-01-15',
                'acquisition_cost' => 12000000,
                'useful_life_months' => 60,
                'salvage_value' => 1000000,
                'depreciation_method' => 'straight_line',
                'condition' => 'good',
                'status' => 'active',
            ], $overrides));
        });
    }

    // ==================== PAGE ACCESS TESTS ====================

    /** @test */
    public function asset_index_page_is_accessible()
    {
        $response = $this->get(route('asset.index'));
        $response->assertStatus(200);
        $response->assertSee('Daftar Aset');
    }

    /** @test */
    public function guest_cannot_access_asset_page()
    {
        auth()->logout();
        $this->get(route('asset.index'))->assertRedirect(route('login'));
    }

    // ==================== LIST COMPONENT TESTS ====================

    /** @test */
    public function asset_list_renders_successfully()
    {
        Livewire::test(AssetList::class)->assertStatus(200);
    }

    /** @test */
    public function asset_list_shows_assets()
    {
        $this->createAsset(['code' => 'AST-001', 'name' => 'Laptop Asus']);
        $this->createAsset(['code' => 'AST-002', 'name' => 'Printer Canon']);

        Livewire::test(AssetList::class)
            ->assertSee('Laptop Asus')
            ->assertSee('Printer Canon');
    }

    /** @test */
    public function asset_list_can_search()
    {
        $this->createAsset(['code' => 'AST-001', 'name' => 'Laptop Asus']);
        $this->createAsset(['code' => 'AST-002', 'name' => 'Printer Canon']);

        Livewire::test(AssetList::class)
            ->set('search', 'Laptop')
            ->assertSee('Laptop Asus')
            ->assertDontSee('Printer Canon');
    }

    /** @test */
    public function asset_list_can_search_by_serial_number()
    {
        $this->createAsset(['code' => 'AST-001', 'name' => 'Laptop', 'serial_number' => 'SN-12345']);
        $this->createAsset(['code' => 'AST-002', 'name' => 'Printer', 'serial_number' => 'SN-99999']);

        Livewire::test(AssetList::class)
            ->set('search', 'SN-12345')
            ->assertSee('Laptop')
            ->assertDontSee('Printer');
    }

    /** @test */
    public function asset_list_can_filter_by_category()
    {
        $cat2 = AssetCategory::withoutEvents(fn() => AssetCategory::create([
            'business_unit_id' => $this->unit->id,
            'code' => 'AC-002',
            'name' => 'Kendaraan',
            'useful_life_months' => 96,
            'depreciation_method' => 'straight_line',
            'is_active' => true,
        ]));

        $this->createAsset(['code' => 'AST-001', 'name' => 'Laptop', 'asset_category_id' => $this->category->id]);
        $this->createAsset(['code' => 'AST-002', 'name' => 'Mobil', 'asset_category_id' => $cat2->id]);

        Livewire::test(AssetList::class)
            ->set('filterCategory', $this->category->id)
            ->assertSee('Laptop')
            ->assertDontSee('Mobil');
    }

    /** @test */
    public function asset_list_can_filter_by_status()
    {
        $this->createAsset(['code' => 'AST-001', 'name' => 'Active Asset', 'status' => 'active']);
        $this->createAsset(['code' => 'AST-002', 'name' => 'Disposed Asset', 'status' => 'disposed']);

        Livewire::test(AssetList::class)
            ->set('filterStatus', 'active')
            ->assertSee('Active Asset')
            ->assertDontSee('Disposed Asset');
    }

    /** @test */
    public function asset_list_can_sort()
    {
        $this->createAsset(['code' => 'ZZZ', 'name' => 'Zebra']);
        $this->createAsset(['code' => 'AAA', 'name' => 'Alpha']);

        Livewire::test(AssetList::class)
            ->call('sortBy', 'name')
            ->assertSeeInOrder(['Alpha', 'Zebra']);
    }

    /** @test */
    public function superadmin_can_filter_assets_by_unit()
    {
        $unit2 = BusinessUnit::withoutEvents(fn() =>
            BusinessUnit::create(['code' => 'UNT-002', 'name' => 'Unit 2', 'is_active' => true])
        );

        $this->createAsset(['code' => 'AST-001', 'name' => 'Asset Unit 1', 'business_unit_id' => $this->unit->id]);
        $this->createAsset(['code' => 'AST-002', 'name' => 'Asset Unit 2', 'business_unit_id' => $unit2->id, 'asset_category_id' => $this->category->id]);

        Livewire::test(AssetList::class)
            ->set('filterUnit', $this->unit->id)
            ->assertSee('Asset Unit 1')
            ->assertDontSee('Asset Unit 2');
    }

    /** @test */
    public function asset_can_be_deleted()
    {
        $asset = $this->createAsset();

        Livewire::test(AssetList::class)
            ->call('deleteAsset', $asset->id)
            ->assertDispatched('alert');

        $this->assertSoftDeleted('assets', ['id' => $asset->id]);
    }

    /** @test */
    public function asset_with_depreciations_cannot_be_deleted()
    {
        $asset = $this->createAsset();

        AssetDepreciation::withoutEvents(fn() => AssetDepreciation::create([
            'asset_id' => $asset->id,
            'period_id' => null,
            'depreciation_date' => now(),
            'depreciation_amount' => 183333,
            'accumulated_depreciation' => 183333,
            'book_value' => 11816667,
        ]));

        Livewire::test(AssetList::class)
            ->call('deleteAsset', $asset->id)
            ->assertDispatched('alert', type: 'error');

        $this->assertDatabaseHas('assets', ['id' => $asset->id, 'deleted_at' => null]);
    }

    // ==================== FORM COMPONENT TESTS ====================

    /** @test */
    public function asset_form_renders_successfully()
    {
        Livewire::test(AssetForm::class)->assertStatus(200);
    }

    /** @test */
    public function asset_form_can_open_modal()
    {
        Livewire::test(AssetForm::class)
            ->call('openAssetModal')
            ->assertSet('showModal', true)
            ->assertSet('isEditing', false);
    }

    /** @test */
    public function asset_form_can_create_asset()
    {
        Livewire::test(AssetForm::class)
            ->call('openAssetModal')
            ->set('business_unit_id', $this->unit->id)
            ->set('asset_category_id', $this->category->id)
            ->set('code', 'AST-NEW')
            ->set('name', 'Monitor LG')
            ->set('acquisition_date', '2025-06-01')
            ->set('acquisition_cost', 3500000)
            ->set('useful_life_months', 60)
            ->set('salvage_value', 500000)
            ->set('depreciation_method', 'straight_line')
            ->set('condition', 'good')
            ->call('save')
            ->assertDispatched('alert', type: 'success')
            ->assertDispatched('refreshAssetList')
            ->assertSet('showModal', false);

        $this->assertDatabaseHas('assets', [
            'code' => 'AST-NEW',
            'name' => 'Monitor LG',
            'acquisition_cost' => 3500000,
            'status' => 'active',
        ]);
    }

    /** @test */
    public function asset_form_can_edit_asset()
    {
        $asset = $this->createAsset();

        Livewire::test(AssetForm::class)
            ->call('editAsset', $asset->id)
            ->assertSet('showModal', true)
            ->assertSet('isEditing', true)
            ->assertSet('code', 'AST-001')
            ->set('name', 'Updated Asset')
            ->set('location', 'Ruang IT')
            ->call('save')
            ->assertDispatched('alert', type: 'success');

        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'name' => 'Updated Asset',
            'location' => 'Ruang IT',
        ]);
    }

    /** @test */
    public function asset_form_validates_required_fields()
    {
        Livewire::test(AssetForm::class)
            ->call('openAssetModal')
            ->set('code', '')
            ->set('name', '')
            ->set('business_unit_id', '')
            ->set('asset_category_id', '')
            ->set('acquisition_date', '')
            ->call('save')
            ->assertHasErrors(['code', 'name', 'business_unit_id', 'asset_category_id', 'acquisition_date']);
    }

    /** @test */
    public function asset_form_validates_unique_code_per_unit()
    {
        $this->createAsset(['code' => 'AST-001']);

        Livewire::test(AssetForm::class)
            ->call('openAssetModal')
            ->set('business_unit_id', $this->unit->id)
            ->set('asset_category_id', $this->category->id)
            ->set('code', 'AST-001')
            ->set('name', 'Duplicate')
            ->set('acquisition_date', '2025-01-01')
            ->set('acquisition_cost', 1000000)
            ->set('useful_life_months', 60)
            ->set('salvage_value', 0)
            ->set('depreciation_method', 'straight_line')
            ->set('condition', 'good')
            ->call('save')
            ->assertHasErrors(['code']);
    }

    /** @test */
    public function asset_form_validates_condition_enum()
    {
        Livewire::test(AssetForm::class)
            ->call('openAssetModal')
            ->set('business_unit_id', $this->unit->id)
            ->set('asset_category_id', $this->category->id)
            ->set('code', 'AST-001')
            ->set('name', 'Test')
            ->set('acquisition_date', '2025-01-01')
            ->set('condition', 'invalid')
            ->call('save')
            ->assertHasErrors(['condition']);
    }

    /** @test */
    public function asset_form_can_close_modal()
    {
        Livewire::test(AssetForm::class)
            ->call('openAssetModal')
            ->assertSet('showModal', true)
            ->call('closeModal')
            ->assertSet('showModal', false);
    }

    /** @test */
    public function asset_form_auto_fills_category_defaults()
    {
        Livewire::test(AssetForm::class)
            ->call('openAssetModal')
            ->set('business_unit_id', $this->unit->id)
            ->set('asset_category_id', $this->category->id)
            ->assertSet('useful_life_months', 60)
            ->assertSet('depreciation_method', 'straight_line');
    }

    // ==================== MODEL TESTS ====================

    /** @test */
    public function asset_has_statuses_constant()
    {
        $statuses = Asset::STATUSES;
        $this->assertArrayHasKey('active', $statuses);
        $this->assertArrayHasKey('disposed', $statuses);
        $this->assertArrayHasKey('under_repair', $statuses);
    }

    /** @test */
    public function asset_has_conditions_constant()
    {
        $conditions = Asset::CONDITIONS;
        $this->assertArrayHasKey('good', $conditions);
        $this->assertArrayHasKey('fair', $conditions);
        $this->assertArrayHasKey('poor', $conditions);
    }

    /** @test */
    public function asset_active_scope_works()
    {
        $this->createAsset(['code' => 'AST-001', 'status' => 'active']);
        $this->createAsset(['code' => 'AST-002', 'status' => 'disposed']);

        $this->assertEquals(1, Asset::active()->count());
    }

    /** @test */
    public function asset_by_business_unit_scope_works()
    {
        $unit2 = BusinessUnit::withoutEvents(fn() =>
            BusinessUnit::create(['code' => 'UNT-002', 'name' => 'Unit 2', 'is_active' => true])
        );

        $this->createAsset(['code' => 'AST-001', 'business_unit_id' => $this->unit->id]);
        $this->createAsset(['code' => 'AST-002', 'business_unit_id' => $unit2->id]);

        $this->assertEquals(1, Asset::byBusinessUnit($this->unit->id)->count());
    }

    /** @test */
    public function asset_by_category_scope_works()
    {
        $cat2 = AssetCategory::withoutEvents(fn() => AssetCategory::create([
            'business_unit_id' => $this->unit->id,
            'code' => 'AC-002',
            'name' => 'Kendaraan',
            'useful_life_months' => 96,
            'depreciation_method' => 'straight_line',
            'is_active' => true,
        ]));

        $this->createAsset(['code' => 'AST-001', 'asset_category_id' => $this->category->id]);
        $this->createAsset(['code' => 'AST-002', 'asset_category_id' => $cat2->id]);

        $this->assertEquals(1, Asset::byCategory($this->category->id)->count());
    }

    /** @test */
    public function asset_belongs_to_business_unit()
    {
        $asset = $this->createAsset();
        $this->assertInstanceOf(BusinessUnit::class, $asset->businessUnit);
    }

    /** @test */
    public function asset_belongs_to_category()
    {
        $asset = $this->createAsset();
        $this->assertInstanceOf(AssetCategory::class, $asset->assetCategory);
    }

    /** @test */
    public function asset_book_value_accessor_works()
    {
        $asset = $this->createAsset(['acquisition_cost' => 12000000, 'salvage_value' => 1000000]);
        // No depreciations yet, book value == acquisition cost
        $this->assertEquals(12000000, $asset->book_value);
    }

    /** @test */
    public function asset_accumulated_depreciation_accessor_works()
    {
        $asset = $this->createAsset();

        AssetDepreciation::withoutEvents(fn() => AssetDepreciation::create([
            'asset_id' => $asset->id,
            'period_id' => null,
            'depreciation_date' => now(),
            'depreciation_amount' => 183333,
            'accumulated_depreciation' => 183333,
            'book_value' => 11816667,
        ]));

        $this->assertEquals(183333, $asset->fresh()->accumulated_depreciation);
    }

    /** @test */
    public function asset_book_value_after_depreciation()
    {
        $asset = $this->createAsset(['acquisition_cost' => 12000000]);

        AssetDepreciation::withoutEvents(fn() => AssetDepreciation::create([
            'asset_id' => $asset->id,
            'period_id' => null,
            'depreciation_date' => now(),
            'depreciation_amount' => 2000000,
            'accumulated_depreciation' => 2000000,
            'book_value' => 10000000,
        ]));

        $freshAsset = $asset->fresh();
        $this->assertEquals(2000000, $freshAsset->accumulated_depreciation);
        $this->assertEquals(10000000, $freshAsset->book_value);
    }

    // ==================== NON-SUPERADMIN TESTS ====================

    /** @test */
    public function non_superadmin_only_sees_own_unit_assets()
    {
        $unit2 = BusinessUnit::withoutEvents(fn() =>
            BusinessUnit::create(['code' => 'UNT-002', 'name' => 'Unit 2', 'is_active' => true])
        );

        $this->createAsset(['code' => 'AST-001', 'name' => 'My Asset', 'business_unit_id' => $this->unit->id]);
        $this->createAsset(['code' => 'AST-002', 'name' => 'Other Asset', 'business_unit_id' => $unit2->id]);

        $regularUser = User::withoutEvents(fn() => User::factory()->create(['business_unit_id' => $this->unit->id]));
        $this->actingAs($regularUser);

        Livewire::test(AssetList::class)
            ->assertSee('My Asset')
            ->assertDontSee('Other Asset');
    }
}
