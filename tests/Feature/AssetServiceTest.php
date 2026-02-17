<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetDepreciation;
use App\Models\BusinessUnit;
use App\Models\User;
use App\Services\AssetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AssetServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected BusinessUnit $unit;
    protected AssetCategory $category;
    protected AssetService $service;

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

        $this->service = app(AssetService::class);
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
                'salvage_value' => 0,
                'depreciation_method' => 'straight_line',
                'condition' => 'good',
                'status' => 'active',
            ], $overrides));
        });
    }

    // ==================== STRAIGHT LINE DEPRECIATION ====================

    /** @test */
    public function straight_line_depreciation_correct_for_zero_salvage()
    {
        $asset = $this->createAsset([
            'acquisition_cost' => 12000000,
            'salvage_value' => 0,
            'useful_life_months' => 60,
            'depreciation_method' => 'straight_line',
        ]);

        $monthly = $this->service->calculateMonthlyDepreciation($asset);
        // 12,000,000 / 60 = 200,000
        $this->assertEquals(200000, $monthly);
    }

    /** @test */
    public function straight_line_depreciation_with_salvage_value()
    {
        $asset = $this->createAsset([
            'acquisition_cost' => 12000000,
            'salvage_value' => 2000000,
            'useful_life_months' => 60,
            'depreciation_method' => 'straight_line',
        ]);

        $monthly = $this->service->calculateMonthlyDepreciation($asset);
        // (12,000,000 - 2,000,000) / 60 = ~166,667
        $expected = (int) round(10000000 / 60);
        $this->assertEquals($expected, $monthly);
    }

    /** @test */
    public function straight_line_depreciation_does_not_exceed_salvage()
    {
        $asset = $this->createAsset([
            'acquisition_cost' => 1000000,
            'salvage_value' => 100000,
            'useful_life_months' => 60,
            'depreciation_method' => 'straight_line',
        ]);

        // Normal monthly: (1,000,000 - 100,000) / 60 = 15,000
        // Depreciate to near salvage (book_value = 110,000, so 10,000 remaining to salvage)
        AssetDepreciation::withoutEvents(fn() => AssetDepreciation::create([
            'asset_id' => $asset->id,
            'period_id' => null,
            'depreciation_date' => now(),
            'depreciation_amount' => 890000,
            'accumulated_depreciation' => 890000,
            'book_value' => 110000,
        ]));

        $monthly = $this->service->calculateMonthlyDepreciation($asset);
        // Book value 110,000 - salvage 100,000 = 10,000
        $this->assertEquals(10000, $monthly);
    }

    /** @test */
    public function no_depreciation_when_book_value_at_salvage()
    {
        $asset = $this->createAsset([
            'acquisition_cost' => 1000000,
            'salvage_value' => 100000,
            'useful_life_months' => 60,
            'depreciation_method' => 'straight_line',
        ]);

        AssetDepreciation::withoutEvents(fn() => AssetDepreciation::create([
            'asset_id' => $asset->id,
            'period_id' => null,
            'depreciation_date' => now(),
            'depreciation_amount' => 900000,
            'accumulated_depreciation' => 900000,
            'book_value' => 100000,
        ]));

        $monthly = $this->service->calculateMonthlyDepreciation($asset);
        $this->assertEquals(0, $monthly);
    }

    // ==================== DECLINING BALANCE DEPRECIATION ====================

    /** @test */
    public function declining_balance_depreciation_calculation()
    {
        $asset = $this->createAsset([
            'acquisition_cost' => 12000000,
            'salvage_value' => 0,
            'useful_life_months' => 60, // 5 years
            'depreciation_method' => 'declining_balance',
        ]);

        $monthly = $this->service->calculateMonthlyDepreciation($asset);
        // Annual rate = 2 / 5 = 0.4, monthly = 12,000,000 * 0.4 / 12 = 400,000
        $expected = (int) round(12000000 * 0.4 / 12);
        $this->assertEquals($expected, $monthly);
    }

    /** @test */
    public function declining_balance_decreases_over_time()
    {
        $asset = $this->createAsset([
            'acquisition_cost' => 10000000,
            'salvage_value' => 0,
            'useful_life_months' => 60,
            'depreciation_method' => 'declining_balance',
        ]);

        $first = $this->service->calculateMonthlyDepreciation($asset);

        // Simulate depreciation
        AssetDepreciation::withoutEvents(fn() => AssetDepreciation::create([
            'asset_id' => $asset->id,
            'period_id' => null,
            'depreciation_date' => now(),
            'depreciation_amount' => 3000000,
            'accumulated_depreciation' => 3000000,
            'book_value' => 7000000,
        ]));

        $second = $this->service->calculateMonthlyDepreciation($asset);

        $this->assertGreaterThan($second, $first);
    }

    // ==================== BOOK VALUE CALCULATIONS ====================

    /** @test */
    public function get_current_book_value_without_depreciations()
    {
        $asset = $this->createAsset(['acquisition_cost' => 12000000]);

        $bookValue = $this->service->getCurrentBookValue($asset);
        $this->assertEquals(12000000, $bookValue);
    }

    /** @test */
    public function get_current_book_value_with_depreciations()
    {
        $asset = $this->createAsset(['acquisition_cost' => 12000000]);

        AssetDepreciation::withoutEvents(fn() => AssetDepreciation::create([
            'asset_id' => $asset->id,
            'period_id' => null,
            'depreciation_date' => now(),
            'depreciation_amount' => 200000,
            'accumulated_depreciation' => 200000,
            'book_value' => 11800000,
        ]));

        AssetDepreciation::withoutEvents(fn() => AssetDepreciation::create([
            'asset_id' => $asset->id,
            'period_id' => null,
            'depreciation_date' => now()->addMonth(),
            'depreciation_amount' => 200000,
            'accumulated_depreciation' => 400000,
            'book_value' => 11600000,
        ]));

        $bookValue = $this->service->getCurrentBookValue($asset);
        $this->assertEquals(11600000, $bookValue);
    }

    /** @test */
    public function book_value_never_goes_below_zero()
    {
        $asset = $this->createAsset(['acquisition_cost' => 1000000, 'salvage_value' => 0]);

        AssetDepreciation::withoutEvents(fn() => AssetDepreciation::create([
            'asset_id' => $asset->id,
            'period_id' => null,
            'depreciation_date' => now(),
            'depreciation_amount' => 1500000, // More than acquisition cost
            'accumulated_depreciation' => 1500000,
            'book_value' => 0,
        ]));

        $bookValue = $this->service->getCurrentBookValue($asset);
        $this->assertEquals(0, $bookValue);
    }

    // ==================== ACCUMULATED DEPRECIATION ====================

    /** @test */
    public function get_accumulated_depreciation_correct()
    {
        $asset = $this->createAsset();

        AssetDepreciation::withoutEvents(fn() => AssetDepreciation::create([
            'asset_id' => $asset->id,
            'period_id' => null,
            'depreciation_date' => now(),
            'depreciation_amount' => 200000,
            'accumulated_depreciation' => 200000,
            'book_value' => 11800000,
        ]));

        AssetDepreciation::withoutEvents(fn() => AssetDepreciation::create([
            'asset_id' => $asset->id,
            'period_id' => null,
            'depreciation_date' => now()->addMonth(),
            'depreciation_amount' => 200000,
            'accumulated_depreciation' => 400000,
            'book_value' => 11600000,
        ]));

        $accumulated = $this->service->getAccumulatedDepreciation($asset);
        $this->assertEquals(400000, $accumulated);
    }

    /** @test */
    public function accumulated_depreciation_zero_when_no_entries()
    {
        $asset = $this->createAsset();
        $accumulated = $this->service->getAccumulatedDepreciation($asset);
        $this->assertEquals(0, $accumulated);
    }

    // ==================== REPORT PAGE ACCESS TESTS ====================

    /** @test */
    public function report_register_page_is_accessible()
    {
        $response = $this->get(route('asset-report.register'));
        $response->assertStatus(200);
    }

    /** @test */
    public function report_book_value_page_is_accessible()
    {
        $response = $this->get(route('asset-report.book-value'));
        $response->assertStatus(200);
    }

    /** @test */
    public function report_depreciation_page_is_accessible()
    {
        $response = $this->get(route('asset-report.depreciation'));
        $response->assertStatus(200);
    }

    /** @test */
    public function report_history_page_is_accessible()
    {
        $response = $this->get(route('asset-report.history'));
        $response->assertStatus(200);
    }

    /** @test */
    public function guest_cannot_access_report_pages()
    {
        auth()->logout();
        $this->get(route('asset-report.register'))->assertRedirect(route('login'));
        $this->get(route('asset-report.book-value'))->assertRedirect(route('login'));
        $this->get(route('asset-report.depreciation'))->assertRedirect(route('login'));
        $this->get(route('asset-report.history'))->assertRedirect(route('login'));
    }

    // ==================== DEPRECIATION PAGE ACCESS TESTS ====================

    /** @test */
    public function depreciation_page_is_accessible()
    {
        $response = $this->get(route('asset-depreciation.index'));
        $response->assertStatus(200);
        $response->assertSee('Penyusutan');
    }

    /** @test */
    public function guest_cannot_access_depreciation_page()
    {
        auth()->logout();
        $this->get(route('asset-depreciation.index'))->assertRedirect(route('login'));
    }
}
