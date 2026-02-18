<?php

namespace Tests\Feature;

use App\Livewire\Saldo\SaldoProductForm;
use App\Livewire\Saldo\SaldoProductList;
use App\Livewire\Saldo\SaldoProviderForm;
use App\Livewire\Saldo\SaldoProviderList;
use App\Livewire\Saldo\SaldoTopupForm;
use App\Livewire\Saldo\SaldoTopupList;
use App\Livewire\Saldo\SaldoTransactionForm;
use App\Livewire\Saldo\SaldoTransactionList;
use App\Models\BusinessUnit;
use App\Models\SaldoProduct;
use App\Models\SaldoProvider;
use App\Models\SaldoTopup;
use App\Models\SaldoTransaction;
use App\Models\User;
use App\Services\SaldoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SaldoManagementTest extends TestCase
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

    // ─── Helper Methods ───

    protected function createProvider(array $overrides = []): SaldoProvider
    {
        return SaldoProvider::withoutEvents(function () use ($overrides) {
            return SaldoProvider::create(array_merge([
                'business_unit_id' => $this->unit->id,
                'code' => 'PRV-001',
                'name' => 'Buku Warung',
                'type' => 'e-wallet',
                'initial_balance' => 0,
                'current_balance' => 0,
                'is_active' => true,
            ], $overrides));
        });
    }

    protected function createProduct(array $overrides = []): SaldoProduct
    {
        return SaldoProduct::withoutEvents(function () use ($overrides) {
            return SaldoProduct::create(array_merge([
                'business_unit_id' => $this->unit->id,
                'code' => 'PRD-001',
                'name' => 'Pulsa 50K',
                'buy_price' => 49950,
                'sell_price' => 52000,
                'is_active' => true,
            ], $overrides));
        });
    }

    protected function createTopup(SaldoProvider $provider, array $overrides = []): SaldoTopup
    {
        return SaldoTopup::withoutEvents(function () use ($provider, $overrides) {
            return SaldoTopup::create(array_merge([
                'business_unit_id' => $this->unit->id,
                'saldo_provider_id' => $provider->id,
                'amount' => 1000000,
                'fee' => 0,
                'topup_date' => now()->format('Y-m-d'),
                'method' => 'transfer',
            ], $overrides));
        });
    }

    protected function createTransaction(SaldoProvider $provider, array $overrides = []): SaldoTransaction
    {
        return SaldoTransaction::withoutEvents(function () use ($provider, $overrides) {
            return SaldoTransaction::create(array_merge([
                'business_unit_id' => $this->unit->id,
                'saldo_provider_id' => $provider->id,
                'customer_name' => 'Budi',
                'customer_phone' => '08123456789',
                'buy_price' => 49950,
                'sell_price' => 52000,
                'profit' => 2050,
                'transaction_date' => now()->format('Y-m-d'),
            ], $overrides));
        });
    }

    // ==================== PAGE ACCESS TESTS ====================

    /** @test */
    public function saldo_provider_page_is_accessible()
    {
        $response = $this->get(route('saldo-provider.index'));
        $response->assertStatus(200);
        $response->assertSee('Penyedia Saldo');
    }

    /** @test */
    public function saldo_product_page_is_accessible()
    {
        $response = $this->get(route('saldo-product.index'));
        $response->assertStatus(200);
        $response->assertSee('Produk Saldo');
    }

    /** @test */
    public function saldo_topup_page_is_accessible()
    {
        $response = $this->get(route('saldo-topup.index'));
        $response->assertStatus(200);
        $response->assertSee('Top Up Saldo');
    }

    /** @test */
    public function saldo_transaction_page_is_accessible()
    {
        $response = $this->get(route('saldo-transaction.index'));
        $response->assertStatus(200);
        $response->assertSee('Transaksi Saldo');
    }

    /** @test */
    public function guest_cannot_access_saldo_provider_page()
    {
        auth()->logout();
        $this->get(route('saldo-provider.index'))->assertRedirect(route('login'));
    }

    /** @test */
    public function guest_cannot_access_saldo_product_page()
    {
        auth()->logout();
        $this->get(route('saldo-product.index'))->assertRedirect(route('login'));
    }

    /** @test */
    public function guest_cannot_access_saldo_topup_page()
    {
        auth()->logout();
        $this->get(route('saldo-topup.index'))->assertRedirect(route('login'));
    }

    /** @test */
    public function guest_cannot_access_saldo_transaction_page()
    {
        auth()->logout();
        $this->get(route('saldo-transaction.index'))->assertRedirect(route('login'));
    }

    // ==================== PROVIDER LIST TESTS ====================

    /** @test */
    public function provider_list_renders_successfully()
    {
        Livewire::test(SaldoProviderList::class)
            ->assertStatus(200);
    }

    /** @test */
    public function provider_list_shows_providers()
    {
        $this->createProvider(['code' => 'PRV-001', 'name' => 'Buku Warung']);
        $this->createProvider(['code' => 'PRV-002', 'name' => 'Dana']);

        Livewire::test(SaldoProviderList::class)
            ->assertSee('Buku Warung')
            ->assertSee('Dana');
    }

    /** @test */
    public function provider_list_can_search()
    {
        $this->createProvider(['code' => 'PRV-001', 'name' => 'Buku Warung']);
        $this->createProvider(['code' => 'PRV-002', 'name' => 'Dana']);

        Livewire::test(SaldoProviderList::class)
            ->set('search', 'Buku')
            ->assertSee('Buku Warung')
            ->assertDontSee('Dana');
    }

    /** @test */
    public function provider_list_can_search_by_code()
    {
        $this->createProvider(['code' => 'PRV-001', 'name' => 'Buku Warung']);
        $this->createProvider(['code' => 'PRV-002', 'name' => 'Dana']);

        Livewire::test(SaldoProviderList::class)
            ->set('search', 'PRV-002')
            ->assertSee('Dana')
            ->assertDontSee('Buku Warung');
    }

    /** @test */
    public function provider_list_can_filter_by_unit()
    {
        $unit2 = BusinessUnit::withoutEvents(function () {
            return BusinessUnit::create(['code' => 'UNT-002', 'name' => 'Unit 2', 'is_active' => true]);
        });

        $this->createProvider(['code' => 'PRV-001', 'name' => 'Unit 1 Provider']);
        SaldoProvider::withoutEvents(function () use ($unit2) {
            return SaldoProvider::create([
                'business_unit_id' => $unit2->id,
                'code' => 'PRV-002',
                'name' => 'Unit 2 Provider',
                'type' => 'bank',
                'initial_balance' => 0,
                'current_balance' => 0,
                'is_active' => true,
            ]);
        });

        Livewire::test(SaldoProviderList::class)
            ->set('filterUnit', $this->unit->id)
            ->assertSee('Unit 1 Provider')
            ->assertDontSee('Unit 2 Provider');
    }

    /** @test */
    public function provider_list_can_filter_by_type()
    {
        $this->createProvider(['code' => 'PRV-001', 'name' => 'Dana', 'type' => 'e-wallet']);
        $this->createProvider(['code' => 'PRV-002', 'name' => 'BCA', 'type' => 'bank']);

        Livewire::test(SaldoProviderList::class)
            ->set('filterType', 'e-wallet')
            ->assertSee('Dana')
            ->assertDontSee('BCA');
    }

    /** @test */
    public function provider_list_can_filter_by_status()
    {
        $this->createProvider(['code' => 'PRV-001', 'name' => 'Active', 'is_active' => true]);
        $this->createProvider(['code' => 'PRV-002', 'name' => 'Inactive', 'is_active' => false]);

        Livewire::test(SaldoProviderList::class)
            ->set('filterStatus', '1')
            ->assertSee('Active')
            ->assertDontSee('Inactive');

        Livewire::test(SaldoProviderList::class)
            ->set('filterStatus', '0')
            ->assertSee('Inactive')
            ->assertDontSee('Active');
    }

    /** @test */
    public function provider_list_can_sort()
    {
        $this->createProvider(['code' => 'PRV-002', 'name' => 'Beta']);
        $this->createProvider(['code' => 'PRV-001', 'name' => 'Alpha']);

        Livewire::test(SaldoProviderList::class)
            ->call('sortBy', 'name')
            ->assertSeeInOrder(['Alpha', 'Beta']);
    }

    /** @test */
    public function provider_list_can_toggle_status()
    {
        $provider = $this->createProvider();

        Livewire::test(SaldoProviderList::class)
            ->call('toggleStatus', $provider->id)
            ->assertDispatched('alert');

        $this->assertFalse($provider->fresh()->is_active);
    }

    /** @test */
    public function provider_list_can_delete_provider_without_relations()
    {
        $provider = $this->createProvider();

        Livewire::test(SaldoProviderList::class)
            ->call('deleteProvider', $provider->id)
            ->assertDispatched('alert', type: 'success');

        $this->assertSoftDeleted('saldo_providers', ['id' => $provider->id]);
    }

    /** @test */
    public function provider_list_prevents_deleting_provider_with_topups()
    {
        $provider = $this->createProvider();
        $this->createTopup($provider);

        Livewire::test(SaldoProviderList::class)
            ->call('deleteProvider', $provider->id)
            ->assertDispatched('alert', type: 'error');

        $this->assertDatabaseHas('saldo_providers', ['id' => $provider->id, 'deleted_at' => null]);
    }

    /** @test */
    public function provider_list_prevents_deleting_provider_with_transactions()
    {
        $provider = $this->createProvider();
        $this->createTransaction($provider);

        Livewire::test(SaldoProviderList::class)
            ->call('deleteProvider', $provider->id)
            ->assertDispatched('alert', type: 'error');

        $this->assertDatabaseHas('saldo_providers', ['id' => $provider->id, 'deleted_at' => null]);
    }

    /** @test */
    public function provider_list_prevents_deleting_provider_with_products()
    {
        $provider = $this->createProvider();
        $this->createProduct(['saldo_provider_id' => $provider->id]);

        Livewire::test(SaldoProviderList::class)
            ->call('deleteProvider', $provider->id)
            ->assertDispatched('alert', type: 'error');

        $this->assertDatabaseHas('saldo_providers', ['id' => $provider->id, 'deleted_at' => null]);
    }

    // ==================== PROVIDER FORM TESTS ====================

    /** @test */
    public function provider_form_renders_successfully()
    {
        Livewire::test(SaldoProviderForm::class)
            ->assertStatus(200);
    }

    /** @test */
    public function provider_form_can_open_modal()
    {
        Livewire::test(SaldoProviderForm::class)
            ->call('openSaldoProviderModal')
            ->assertSet('showModal', true)
            ->assertSet('isEditing', false);
    }

    /** @test */
    public function provider_form_can_create_provider()
    {
        Livewire::test(SaldoProviderForm::class)
            ->call('openSaldoProviderModal')
            ->set('business_unit_id', $this->unit->id)
            ->set('code', 'PRV-NEW')
            ->set('name', 'Shopee Pay')
            ->set('type', 'e-wallet')
            ->set('initial_balance', 500000)
            ->call('save')
            ->assertDispatched('alert', type: 'success')
            ->assertDispatched('refreshSaldoProviderList')
            ->assertSet('showModal', false);

        $this->assertDatabaseHas('saldo_providers', [
            'business_unit_id' => $this->unit->id,
            'code' => 'PRV-NEW',
            'name' => 'Shopee Pay',
            'type' => 'e-wallet',
            'initial_balance' => 500000,
            'current_balance' => 500000,
        ]);
    }

    /** @test */
    public function provider_form_sets_current_balance_equal_to_initial_on_create()
    {
        Livewire::test(SaldoProviderForm::class)
            ->call('openSaldoProviderModal')
            ->set('business_unit_id', $this->unit->id)
            ->set('code', 'PRV-BAL')
            ->set('name', 'Balance Test')
            ->set('type', 'bank')
            ->set('initial_balance', 1000000)
            ->call('save');

        $provider = SaldoProvider::where('code', 'PRV-BAL')->first();
        $this->assertEquals(1000000, $provider->current_balance);
        $this->assertEquals(1000000, $provider->initial_balance);
    }

    /** @test */
    public function provider_form_can_edit_provider()
    {
        $provider = $this->createProvider(['initial_balance' => 100000, 'current_balance' => 100000]);

        Livewire::test(SaldoProviderForm::class)
            ->call('editSaldoProvider', $provider->id)
            ->assertSet('showModal', true)
            ->assertSet('isEditing', true)
            ->assertSet('code', 'PRV-001')
            ->set('name', 'Updated Provider')
            ->call('save')
            ->assertDispatched('alert', type: 'success');

        $this->assertDatabaseHas('saldo_providers', [
            'id' => $provider->id,
            'name' => 'Updated Provider',
        ]);
    }

    /** @test */
    public function provider_form_adjusts_current_balance_on_initial_balance_change()
    {
        $provider = $this->createProvider([
            'initial_balance' => 100000,
            'current_balance' => 150000, // has 50k extra from topups
        ]);

        Livewire::test(SaldoProviderForm::class)
            ->call('editSaldoProvider', $provider->id)
            ->set('initial_balance', 200000) // increase initial by 100k
            ->call('save');

        $provider->refresh();
        // current_balance should be: 150000 + (200000 - 100000) = 250000
        $this->assertEquals(250000, $provider->current_balance);
    }

    /** @test */
    public function provider_form_validates_required_fields()
    {
        Livewire::test(SaldoProviderForm::class)
            ->call('openSaldoProviderModal')
            ->set('business_unit_id', '')
            ->set('code', '')
            ->set('name', '')
            ->set('type', '')
            ->call('save')
            ->assertHasErrors(['business_unit_id', 'code', 'name', 'type']);
    }

    /** @test */
    public function provider_form_validates_unique_code_per_unit()
    {
        $this->createProvider(['code' => 'PRV-001']);

        Livewire::test(SaldoProviderForm::class)
            ->call('openSaldoProviderModal')
            ->set('business_unit_id', $this->unit->id)
            ->set('code', 'PRV-001')
            ->set('name', 'Duplicate Code')
            ->set('type', 'bank')
            ->call('save')
            ->assertHasErrors(['code']);
    }

    /** @test */
    public function provider_form_allows_same_code_in_different_units()
    {
        $this->createProvider(['code' => 'PRV-001']);

        $unit2 = BusinessUnit::withoutEvents(function () {
            return BusinessUnit::create(['code' => 'UNT-002', 'name' => 'Unit 2', 'is_active' => true]);
        });

        Livewire::test(SaldoProviderForm::class)
            ->call('openSaldoProviderModal')
            ->set('business_unit_id', $unit2->id)
            ->set('code', 'PRV-001')
            ->set('name', 'Same Code Different Unit')
            ->set('type', 'bank')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('saldo_providers', 2);
    }

    /** @test */
    public function provider_form_can_close_modal()
    {
        Livewire::test(SaldoProviderForm::class)
            ->call('openSaldoProviderModal')
            ->assertSet('showModal', true)
            ->call('closeModal')
            ->assertSet('showModal', false);
    }

    // ==================== PRODUCT LIST TESTS ====================

    /** @test */
    public function product_list_renders_successfully()
    {
        Livewire::test(SaldoProductList::class)
            ->assertStatus(200);
    }

    /** @test */
    public function product_list_shows_products()
    {
        $this->createProduct(['code' => 'PRD-001', 'name' => 'Pulsa 50K']);
        $this->createProduct(['code' => 'PRD-002', 'name' => 'Token 100K']);

        Livewire::test(SaldoProductList::class)
            ->assertSee('Pulsa 50K')
            ->assertSee('Token 100K');
    }

    /** @test */
    public function product_list_can_search()
    {
        $this->createProduct(['code' => 'PRD-001', 'name' => 'Pulsa 50K']);
        $this->createProduct(['code' => 'PRD-002', 'name' => 'Token 100K']);

        Livewire::test(SaldoProductList::class)
            ->set('search', 'Pulsa')
            ->assertSee('Pulsa 50K')
            ->assertDontSee('Token 100K');
    }

    /** @test */
    public function product_list_can_filter_by_provider()
    {
        $provider1 = $this->createProvider(['code' => 'PRV-001', 'name' => 'Dana']);
        $provider2 = $this->createProvider(['code' => 'PRV-002', 'name' => 'Shopee']);

        $this->createProduct(['code' => 'PRD-001', 'name' => 'Prod Dana', 'saldo_provider_id' => $provider1->id]);
        $this->createProduct(['code' => 'PRD-002', 'name' => 'Prod Shopee', 'saldo_provider_id' => $provider2->id]);

        Livewire::test(SaldoProductList::class)
            ->set('filterProvider', $provider1->id)
            ->assertSee('Prod Dana')
            ->assertDontSee('Prod Shopee');
    }

    /** @test */
    public function product_list_can_filter_by_status()
    {
        $this->createProduct(['code' => 'PRD-001', 'name' => 'Active Prod', 'is_active' => true]);
        $this->createProduct(['code' => 'PRD-002', 'name' => 'Inactive Prod', 'is_active' => false]);

        Livewire::test(SaldoProductList::class)
            ->set('filterStatus', '1')
            ->assertSee('Active Prod')
            ->assertDontSee('Inactive Prod');
    }

    /** @test */
    public function product_list_can_sort()
    {
        $this->createProduct(['code' => 'PRD-002', 'name' => 'Beta']);
        $this->createProduct(['code' => 'PRD-001', 'name' => 'Alpha']);

        Livewire::test(SaldoProductList::class)
            ->call('sortBy', 'name')
            ->assertSeeInOrder(['Alpha', 'Beta']);
    }

    /** @test */
    public function product_list_can_toggle_status()
    {
        $product = $this->createProduct();

        Livewire::test(SaldoProductList::class)
            ->call('toggleStatus', $product->id)
            ->assertDispatched('alert');

        $this->assertFalse($product->fresh()->is_active);
    }

    /** @test */
    public function product_list_can_delete_product_without_transactions()
    {
        $product = $this->createProduct();

        Livewire::test(SaldoProductList::class)
            ->call('deleteProduct', $product->id)
            ->assertDispatched('alert', type: 'success');

        $this->assertSoftDeleted('saldo_products', ['id' => $product->id]);
    }

    /** @test */
    public function product_list_prevents_deleting_product_with_transactions()
    {
        $provider = $this->createProvider();
        $product = $this->createProduct(['saldo_provider_id' => $provider->id]);
        $this->createTransaction($provider, ['saldo_product_id' => $product->id]);

        Livewire::test(SaldoProductList::class)
            ->call('deleteProduct', $product->id)
            ->assertDispatched('alert', type: 'error');

        $this->assertDatabaseHas('saldo_products', ['id' => $product->id, 'deleted_at' => null]);
    }

    // ==================== PRODUCT FORM TESTS ====================

    /** @test */
    public function product_form_renders_successfully()
    {
        Livewire::test(SaldoProductForm::class)
            ->assertStatus(200);
    }

    /** @test */
    public function product_form_can_open_modal()
    {
        Livewire::test(SaldoProductForm::class)
            ->call('openSaldoProductModal')
            ->assertSet('showModal', true)
            ->assertSet('isEditing', false);
    }

    /** @test */
    public function product_form_can_create_product()
    {
        Livewire::test(SaldoProductForm::class)
            ->call('openSaldoProductModal')
            ->set('business_unit_id', $this->unit->id)
            ->set('code', 'PRD-NEW')
            ->set('name', 'Token Listrik 200K')
            ->set('buy_price', 198000)
            ->set('sell_price', 202000)
            ->call('save')
            ->assertDispatched('alert', type: 'success')
            ->assertDispatched('refreshSaldoProductList')
            ->assertSet('showModal', false);

        $this->assertDatabaseHas('saldo_products', [
            'code' => 'PRD-NEW',
            'name' => 'Token Listrik 200K',
            'buy_price' => 198000,
            'sell_price' => 202000,
        ]);
    }

    /** @test */
    public function product_form_can_create_product_with_provider()
    {
        $provider = $this->createProvider();

        Livewire::test(SaldoProductForm::class)
            ->call('openSaldoProductModal')
            ->set('business_unit_id', $this->unit->id)
            ->set('code', 'PRD-PROV')
            ->set('name', 'Linked Product')
            ->set('saldo_provider_id', $provider->id)
            ->set('buy_price', 10000)
            ->set('sell_price', 12000)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('saldo_products', [
            'code' => 'PRD-PROV',
            'saldo_provider_id' => $provider->id,
        ]);
    }

    /** @test */
    public function product_form_can_edit_product()
    {
        $product = $this->createProduct();

        Livewire::test(SaldoProductForm::class)
            ->call('editSaldoProduct', $product->id)
            ->assertSet('showModal', true)
            ->assertSet('isEditing', true)
            ->assertSet('code', 'PRD-001')
            ->set('name', 'Updated Product')
            ->set('sell_price', 55000)
            ->call('save')
            ->assertDispatched('alert', type: 'success');

        $this->assertDatabaseHas('saldo_products', [
            'id' => $product->id,
            'name' => 'Updated Product',
            'sell_price' => 55000,
        ]);
    }

    /** @test */
    public function product_form_validates_required_fields()
    {
        Livewire::test(SaldoProductForm::class)
            ->call('openSaldoProductModal')
            ->set('business_unit_id', '')
            ->set('code', '')
            ->set('name', '')
            ->call('save')
            ->assertHasErrors(['business_unit_id', 'code', 'name']);
    }

    /** @test */
    public function product_form_validates_unique_code_per_unit()
    {
        $this->createProduct(['code' => 'PRD-001']);

        Livewire::test(SaldoProductForm::class)
            ->call('openSaldoProductModal')
            ->set('business_unit_id', $this->unit->id)
            ->set('code', 'PRD-001')
            ->set('name', 'Dup')
            ->set('buy_price', 100)
            ->set('sell_price', 200)
            ->call('save')
            ->assertHasErrors(['code']);
    }

    /** @test */
    public function product_form_can_close_modal()
    {
        Livewire::test(SaldoProductForm::class)
            ->call('openSaldoProductModal')
            ->assertSet('showModal', true)
            ->call('closeModal')
            ->assertSet('showModal', false);
    }

    // ==================== TOPUP LIST TESTS ====================

    /** @test */
    public function topup_list_renders_successfully()
    {
        Livewire::test(SaldoTopupList::class)
            ->assertStatus(200);
    }

    /** @test */
    public function topup_list_shows_topups()
    {
        $provider = $this->createProvider(['code' => 'PRV-001', 'name' => 'Dana']);
        $this->createTopup($provider, ['reference_no' => 'REF-001']);
        $this->createTopup($provider, ['reference_no' => 'REF-002']);

        Livewire::test(SaldoTopupList::class)
            ->assertSee('REF-001')
            ->assertSee('REF-002');
    }

    /** @test */
    public function topup_list_can_search_by_reference()
    {
        $provider = $this->createProvider();
        $this->createTopup($provider, ['reference_no' => 'REF-ALPHA']);
        $this->createTopup($provider, ['reference_no' => 'REF-BETA']);

        Livewire::test(SaldoTopupList::class)
            ->set('search', 'ALPHA')
            ->assertSee('REF-ALPHA')
            ->assertDontSee('REF-BETA');
    }

    /** @test */
    public function topup_list_can_filter_by_provider()
    {
        $provider1 = $this->createProvider(['code' => 'PRV-001', 'name' => 'Dana']);
        $provider2 = $this->createProvider(['code' => 'PRV-002', 'name' => 'OVO']);

        $this->createTopup($provider1, ['reference_no' => 'TU-DANA']);
        $this->createTopup($provider2, ['reference_no' => 'TU-OVO']);

        Livewire::test(SaldoTopupList::class)
            ->set('filterProvider', $provider1->id)
            ->assertSee('TU-DANA')
            ->assertDontSee('TU-OVO');
    }

    /** @test */
    public function topup_list_can_filter_by_method()
    {
        $provider = $this->createProvider();
        $this->createTopup($provider, ['reference_no' => 'TU-TRF', 'method' => 'transfer']);
        $this->createTopup($provider, ['reference_no' => 'TU-CASH', 'method' => 'cash']);

        Livewire::test(SaldoTopupList::class)
            ->set('filterMethod', 'transfer')
            ->assertSee('TU-TRF')
            ->assertDontSee('TU-CASH');
    }

    /** @test */
    public function topup_list_can_delete_topup_and_reverse_balance()
    {
        $provider = $this->createProvider(['current_balance' => 1000000]);
        $topup = $this->createTopup($provider, ['amount' => 500000, 'fee' => 5000]);

        Livewire::test(SaldoTopupList::class)
            ->call('deleteTopup', $topup->id)
            ->assertDispatched('alert', type: 'success');

        $this->assertSoftDeleted('saldo_topups', ['id' => $topup->id]);
        // Balance should decrease by net = 500000 - 5000 = 495000
        $this->assertEquals(505000, $provider->fresh()->current_balance);
    }

    // ==================== TOPUP FORM TESTS ====================

    /** @test */
    public function topup_form_renders_successfully()
    {
        Livewire::test(SaldoTopupForm::class)
            ->assertStatus(200);
    }

    /** @test */
    public function topup_form_can_open_modal()
    {
        Livewire::test(SaldoTopupForm::class)
            ->call('openSaldoTopupModal')
            ->assertSet('showModal', true)
            ->assertSet('isEditing', false);
    }

    /** @test */
    public function topup_form_can_create_topup()
    {
        $provider = $this->createProvider(['current_balance' => 0]);

        Livewire::test(SaldoTopupForm::class)
            ->call('openSaldoTopupModal')
            ->set('business_unit_id', $this->unit->id)
            ->set('saldo_provider_id', $provider->id)
            ->set('amount', 1000000)
            ->set('fee', 2500)
            ->set('topup_date', '2026-01-15')
            ->set('method', 'transfer')
            ->set('reference_no', 'REF-TEST')
            ->call('save')
            ->assertDispatched('alert', type: 'success')
            ->assertDispatched('refreshSaldoTopupList')
            ->assertSet('showModal', false);

        $this->assertDatabaseHas('saldo_topups', [
            'saldo_provider_id' => $provider->id,
            'amount' => 1000000,
            'fee' => 2500,
            'method' => 'transfer',
        ]);

        // Balance should increase by net = 1000000 - 2500 = 997500
        $this->assertEquals(997500, $provider->fresh()->current_balance);
    }

    /** @test */
    public function topup_form_can_edit_topup()
    {
        $provider = $this->createProvider(['current_balance' => 1000000]);
        $topup = $this->createTopup($provider, ['amount' => 500000, 'fee' => 0]);

        Livewire::test(SaldoTopupForm::class)
            ->call('editSaldoTopup', $topup->id)
            ->assertSet('showModal', true)
            ->assertSet('isEditing', true)
            ->set('amount', 600000) // New amount
            ->set('fee', 1000)
            ->call('save')
            ->assertDispatched('alert', type: 'success');

        // Old topup reversed: -500000. New created: +599000 (600000-1000)
        // Net change: 1000000 - 500000 + 599000 = 1099000
        $this->assertEquals(1099000, $provider->fresh()->current_balance);
    }

    /** @test */
    public function topup_form_validates_required_fields()
    {
        Livewire::test(SaldoTopupForm::class)
            ->call('openSaldoTopupModal')
            ->set('business_unit_id', '')
            ->set('saldo_provider_id', '')
            ->set('amount', 0)
            ->set('topup_date', '')
            ->set('method', '')
            ->call('save')
            ->assertHasErrors(['business_unit_id', 'saldo_provider_id', 'amount', 'topup_date', 'method']);
    }

    /** @test */
    public function topup_form_can_close_modal()
    {
        Livewire::test(SaldoTopupForm::class)
            ->call('openSaldoTopupModal')
            ->assertSet('showModal', true)
            ->call('closeModal')
            ->assertSet('showModal', false);
    }

    // ==================== TRANSACTION LIST TESTS ====================

    /** @test */
    public function transaction_list_renders_successfully()
    {
        Livewire::test(SaldoTransactionList::class)
            ->assertStatus(200);
    }

    /** @test */
    public function transaction_list_shows_transactions()
    {
        $provider = $this->createProvider();
        $this->createTransaction($provider, ['customer_name' => 'Budi']);
        $this->createTransaction($provider, ['customer_name' => 'Ani']);

        Livewire::test(SaldoTransactionList::class)
            ->assertSee('Budi')
            ->assertSee('Ani');
    }

    /** @test */
    public function transaction_list_can_search_by_customer()
    {
        $provider = $this->createProvider();
        $this->createTransaction($provider, ['customer_name' => 'Budi']);
        $this->createTransaction($provider, ['customer_name' => 'Ani']);

        Livewire::test(SaldoTransactionList::class)
            ->set('search', 'Budi')
            ->assertSee('Budi')
            ->assertDontSee('Ani');
    }

    /** @test */
    public function transaction_list_can_filter_by_provider()
    {
        $provider1 = $this->createProvider(['code' => 'PRV-001', 'name' => 'Dana']);
        $provider2 = $this->createProvider(['code' => 'PRV-002', 'name' => 'OVO']);

        $this->createTransaction($provider1, ['customer_name' => 'Via Dana']);
        $this->createTransaction($provider2, ['customer_name' => 'Via OVO']);

        Livewire::test(SaldoTransactionList::class)
            ->set('filterProvider', $provider1->id)
            ->assertSee('Via Dana')
            ->assertDontSee('Via OVO');
    }

    /** @test */
    public function transaction_list_can_filter_by_product()
    {
        $provider = $this->createProvider();
        $product1 = $this->createProduct(['code' => 'PRD-001', 'name' => 'Pulsa 50K']);
        $product2 = $this->createProduct(['code' => 'PRD-002', 'name' => 'Token 100K']);

        $this->createTransaction($provider, [
            'customer_name' => 'Pulsa Buyer',
            'saldo_product_id' => $product1->id,
        ]);
        $this->createTransaction($provider, [
            'customer_name' => 'Token Buyer',
            'saldo_product_id' => $product2->id,
        ]);

        Livewire::test(SaldoTransactionList::class)
            ->set('filterProduct', $product1->id)
            ->assertSee('Pulsa Buyer')
            ->assertDontSee('Token Buyer');
    }

    /** @test */
    public function transaction_list_can_delete_transaction_and_reverse_balance()
    {
        $provider = $this->createProvider(['current_balance' => 500000]);
        $transaction = $this->createTransaction($provider, ['buy_price' => 49950]);

        Livewire::test(SaldoTransactionList::class)
            ->call('deleteTransaction', $transaction->id)
            ->assertDispatched('alert', type: 'success');

        $this->assertSoftDeleted('saldo_transactions', ['id' => $transaction->id]);
        // Balance restored: 500000 + 49950 = 549950
        $this->assertEquals(549950, $provider->fresh()->current_balance);
    }

    // ==================== TRANSACTION FORM TESTS ====================

    /** @test */
    public function transaction_form_renders_successfully()
    {
        Livewire::test(SaldoTransactionForm::class)
            ->assertStatus(200);
    }

    /** @test */
    public function transaction_form_can_open_modal()
    {
        Livewire::test(SaldoTransactionForm::class)
            ->call('openSaldoTransactionModal')
            ->assertSet('showModal', true)
            ->assertSet('isEditing', false);
    }

    /** @test */
    public function transaction_form_can_create_transaction()
    {
        $provider = $this->createProvider(['current_balance' => 1000000]);

        Livewire::test(SaldoTransactionForm::class)
            ->call('openSaldoTransactionModal')
            ->set('business_unit_id', $this->unit->id)
            ->set('saldo_provider_id', $provider->id)
            ->set('customer_name', 'Budi')
            ->set('customer_phone', '08123456789')
            ->set('buy_price', 49950)
            ->set('sell_price', 52000)
            ->set('transaction_date', '2026-01-15')
            ->call('save')
            ->assertDispatched('alert', type: 'success')
            ->assertDispatched('refreshSaldoTransactionList')
            ->assertSet('showModal', false);

        $this->assertDatabaseHas('saldo_transactions', [
            'saldo_provider_id' => $provider->id,
            'customer_name' => 'Budi',
            'buy_price' => 49950,
            'sell_price' => 52000,
            'profit' => 2050,
        ]);

        // Balance should decrease by buy_price
        $this->assertEquals(950050, $provider->fresh()->current_balance);
    }

    /** @test */
    public function transaction_form_auto_fills_prices_from_product()
    {
        $product = $this->createProduct(['buy_price' => 49950, 'sell_price' => 52000]);

        Livewire::test(SaldoTransactionForm::class)
            ->call('openSaldoTransactionModal')
            ->set('saldo_product_id', $product->id)
            ->assertSet('buy_price', 49950)
            ->assertSet('sell_price', 52000);
    }

    /** @test */
    public function transaction_form_auto_fills_provider_from_product()
    {
        $provider = $this->createProvider();
        $product = $this->createProduct([
            'saldo_provider_id' => $provider->id,
            'buy_price' => 10000,
            'sell_price' => 12000,
        ]);

        Livewire::test(SaldoTransactionForm::class)
            ->call('openSaldoTransactionModal')
            ->set('saldo_product_id', $product->id)
            ->assertSet('saldo_provider_id', $provider->id);
    }

    /** @test */
    public function transaction_form_can_edit_transaction()
    {
        $provider = $this->createProvider(['current_balance' => 1000000]);
        $transaction = $this->createTransaction($provider, [
            'buy_price' => 49950,
            'sell_price' => 52000,
            'profit' => 2050,
        ]);

        Livewire::test(SaldoTransactionForm::class)
            ->call('editSaldoTransaction', $transaction->id)
            ->assertSet('showModal', true)
            ->assertSet('isEditing', true)
            ->set('buy_price', 45000)
            ->set('sell_price', 50000)
            ->call('save')
            ->assertDispatched('alert', type: 'success');

        // Old reversed (+49950), new deducted (-45000)
        // 1000000 + 49950 - 45000 = 1004950
        $this->assertEquals(1004950, $provider->fresh()->current_balance);
    }

    /** @test */
    public function transaction_form_validates_required_fields()
    {
        Livewire::test(SaldoTransactionForm::class)
            ->call('openSaldoTransactionModal')
            ->set('business_unit_id', '')
            ->set('saldo_provider_id', '')
            ->set('transaction_date', '')
            ->call('save')
            ->assertHasErrors(['business_unit_id', 'saldo_provider_id', 'transaction_date']);
    }

    /** @test */
    public function transaction_form_can_close_modal()
    {
        Livewire::test(SaldoTransactionForm::class)
            ->call('openSaldoTransactionModal')
            ->assertSet('showModal', true)
            ->call('closeModal')
            ->assertSet('showModal', false);
    }

    // ==================== SALDO SERVICE TESTS ====================

    /** @test */
    public function service_create_topup_increases_provider_balance()
    {
        $provider = $this->createProvider(['current_balance' => 0]);
        $service = new SaldoService();

        $topup = $service->createTopup([
            'business_unit_id' => $this->unit->id,
            'saldo_provider_id' => $provider->id,
            'amount' => 1000000,
            'fee' => 5000,
            'topup_date' => now()->format('Y-m-d'),
            'method' => 'transfer',
        ]);

        $this->assertInstanceOf(SaldoTopup::class, $topup);
        $this->assertEquals(995000, $provider->fresh()->current_balance);
    }

    /** @test */
    public function service_create_topup_with_zero_fee()
    {
        $provider = $this->createProvider(['current_balance' => 100000]);
        $service = new SaldoService();

        $service->createTopup([
            'business_unit_id' => $this->unit->id,
            'saldo_provider_id' => $provider->id,
            'amount' => 500000,
            'fee' => 0,
            'topup_date' => now()->format('Y-m-d'),
            'method' => 'cash',
        ]);

        $this->assertEquals(600000, $provider->fresh()->current_balance);
    }

    /** @test */
    public function service_delete_topup_reverses_balance()
    {
        $provider = $this->createProvider(['current_balance' => 995000]);
        $topup = $this->createTopup($provider, ['amount' => 1000000, 'fee' => 5000]);

        $service = new SaldoService();
        $service->deleteTopup($topup);

        $this->assertSoftDeleted('saldo_topups', ['id' => $topup->id]);
        $this->assertEquals(0, $provider->fresh()->current_balance);
    }

    /** @test */
    public function service_create_transaction_decreases_provider_balance()
    {
        $provider = $this->createProvider(['current_balance' => 1000000]);
        $service = new SaldoService();

        $transaction = $service->createTransaction([
            'business_unit_id' => $this->unit->id,
            'saldo_provider_id' => $provider->id,
            'customer_name' => 'Budi',
            'buy_price' => 49950,
            'sell_price' => 52000,
            'transaction_date' => now()->format('Y-m-d'),
        ]);

        $this->assertInstanceOf(SaldoTransaction::class, $transaction);
        $this->assertEquals(2050, $transaction->profit);
        $this->assertEquals(950050, $provider->fresh()->current_balance);
    }

    /** @test */
    public function service_delete_transaction_reverses_balance()
    {
        $provider = $this->createProvider(['current_balance' => 950050]);
        $transaction = $this->createTransaction($provider, ['buy_price' => 49950]);

        $service = new SaldoService();
        $service->deleteTransaction($transaction);

        $this->assertSoftDeleted('saldo_transactions', ['id' => $transaction->id]);
        $this->assertEquals(1000000, $provider->fresh()->current_balance);
    }

    /** @test */
    public function service_get_balance_summary()
    {
        $provider1 = $this->createProvider([
            'code' => 'PRV-001',
            'name' => 'Dana',
            'current_balance' => 500000,
        ]);
        $provider2 = $this->createProvider([
            'code' => 'PRV-002',
            'name' => 'OVO',
            'current_balance' => 300000,
        ]);

        $this->createTopup($provider1, ['amount' => 1000000, 'fee' => 5000]);
        $this->createTopup($provider2, ['amount' => 500000, 'fee' => 2500]);

        $this->createTransaction($provider1, [
            'buy_price' => 49950,
            'sell_price' => 52000,
            'profit' => 2050,
        ]);

        $service = new SaldoService();
        $summary = $service->getBalanceSummary($this->unit->id);

        $this->assertEquals(800000, $summary['total_balance']); // 500000 + 300000
        $this->assertEquals(1500000, $summary['total_topups']); // 1000000 + 500000
        $this->assertEquals(52000, $summary['total_transactions']); // sell_price sum
        $this->assertEquals(2050, $summary['total_profit']);
        $this->assertCount(2, $summary['providers']);
    }

    /** @test */
    public function service_calculate_profit_correctly()
    {
        $provider = $this->createProvider(['current_balance' => 1000000]);
        $service = new SaldoService();

        $transaction = $service->createTransaction([
            'business_unit_id' => $this->unit->id,
            'saldo_provider_id' => $provider->id,
            'buy_price' => 97000,
            'sell_price' => 102000,
            'transaction_date' => now()->format('Y-m-d'),
        ]);

        $this->assertEquals(5000, $transaction->profit);
    }

    // ==================== MODEL TESTS ====================

    /** @test */
    public function saldo_provider_has_correct_types()
    {
        $types = SaldoProvider::getTypes();
        $this->assertEquals(['e-wallet' => 'E-Wallet', 'bank' => 'Bank', 'other' => 'Lainnya'], $types);
    }

    /** @test */
    public function saldo_topup_has_correct_methods()
    {
        $methods = SaldoTopup::getMethods();
        $this->assertArrayHasKey('transfer', $methods);
        $this->assertArrayHasKey('cash', $methods);
        $this->assertArrayHasKey('e-wallet', $methods);
        $this->assertArrayHasKey('other', $methods);
    }

    /** @test */
    public function saldo_provider_belongs_to_business_unit()
    {
        $provider = $this->createProvider();
        $this->assertInstanceOf(BusinessUnit::class, $provider->businessUnit);
    }

    /** @test */
    public function saldo_provider_has_many_products()
    {
        $provider = $this->createProvider();
        $this->createProduct(['saldo_provider_id' => $provider->id]);

        $this->assertCount(1, $provider->fresh()->products);
    }

    /** @test */
    public function saldo_provider_has_many_topups()
    {
        $provider = $this->createProvider();
        $this->createTopup($provider);

        $this->assertCount(1, $provider->fresh()->topups);
    }

    /** @test */
    public function saldo_provider_has_many_transactions()
    {
        $provider = $this->createProvider();
        $this->createTransaction($provider);

        $this->assertCount(1, $provider->fresh()->transactions);
    }

    /** @test */
    public function saldo_product_belongs_to_provider()
    {
        $provider = $this->createProvider();
        $product = $this->createProduct(['saldo_provider_id' => $provider->id]);

        $this->assertInstanceOf(SaldoProvider::class, $product->saldoProvider);
    }

    /** @test */
    public function saldo_product_has_profit_margin_accessor()
    {
        $product = $this->createProduct(['buy_price' => 49950, 'sell_price' => 52000]);

        // margin = ((52000 - 49950) / 49950) * 100 = 4.10...
        $this->assertGreaterThan(4, $product->profit_margin);
        $this->assertLessThan(5, $product->profit_margin);
    }

    /** @test */
    public function saldo_product_profit_margin_zero_when_buy_price_zero()
    {
        $product = $this->createProduct(['buy_price' => 0, 'sell_price' => 52000]);
        $this->assertEquals(0, $product->profit_margin);
    }

    /** @test */
    public function saldo_topup_net_amount_accessor()
    {
        $provider = $this->createProvider();
        $topup = $this->createTopup($provider, ['amount' => 1000000, 'fee' => 5000]);

        $this->assertEquals(995000, $topup->net_amount);
    }

    /** @test */
    public function saldo_topup_belongs_to_provider()
    {
        $provider = $this->createProvider();
        $topup = $this->createTopup($provider);

        $this->assertInstanceOf(SaldoProvider::class, $topup->saldoProvider);
    }

    /** @test */
    public function saldo_transaction_belongs_to_provider()
    {
        $provider = $this->createProvider();
        $transaction = $this->createTransaction($provider);

        $this->assertInstanceOf(SaldoProvider::class, $transaction->saldoProvider);
    }

    /** @test */
    public function saldo_transaction_belongs_to_product()
    {
        $provider = $this->createProvider();
        $product = $this->createProduct(['saldo_provider_id' => $provider->id]);
        $transaction = $this->createTransaction($provider, ['saldo_product_id' => $product->id]);

        $this->assertInstanceOf(SaldoProduct::class, $transaction->saldoProduct);
    }

    // ==================== SCOPE TESTS ====================

    /** @test */
    public function provider_active_scope_works()
    {
        $this->createProvider(['code' => 'PRV-001', 'is_active' => true]);
        $this->createProvider(['code' => 'PRV-002', 'is_active' => false]);

        $this->assertEquals(1, SaldoProvider::active()->count());
    }

    /** @test */
    public function provider_by_business_unit_scope_works()
    {
        $unit2 = BusinessUnit::withoutEvents(fn () => BusinessUnit::create(['code' => 'UNT-002', 'name' => 'Unit 2', 'is_active' => true]));

        $this->createProvider(['code' => 'PRV-001']);
        SaldoProvider::withoutEvents(fn () => SaldoProvider::create([
            'business_unit_id' => $unit2->id, 'code' => 'PRV-002', 'name' => 'Other',
            'type' => 'bank', 'initial_balance' => 0, 'current_balance' => 0, 'is_active' => true,
        ]));

        $this->assertEquals(1, SaldoProvider::byBusinessUnit($this->unit->id)->count());
    }

    /** @test */
    public function provider_by_type_scope_works()
    {
        $this->createProvider(['code' => 'PRV-001', 'type' => 'e-wallet']);
        $this->createProvider(['code' => 'PRV-002', 'type' => 'bank']);
        $this->createProvider(['code' => 'PRV-003', 'type' => 'other']);

        $this->assertEquals(1, SaldoProvider::byType('e-wallet')->count());
        $this->assertEquals(1, SaldoProvider::byType('bank')->count());
        $this->assertEquals(1, SaldoProvider::byType('other')->count());
    }

    /** @test */
    public function product_active_scope_works()
    {
        $this->createProduct(['code' => 'PRD-001', 'is_active' => true]);
        $this->createProduct(['code' => 'PRD-002', 'is_active' => false]);

        $this->assertEquals(1, SaldoProduct::active()->count());
    }

    /** @test */
    public function topup_by_provider_scope_works()
    {
        $provider1 = $this->createProvider(['code' => 'PRV-001']);
        $provider2 = $this->createProvider(['code' => 'PRV-002']);

        $this->createTopup($provider1);
        $this->createTopup($provider2);

        $this->assertEquals(1, SaldoTopup::byProvider($provider1->id)->count());
    }

    /** @test */
    public function transaction_by_provider_scope_works()
    {
        $provider1 = $this->createProvider(['code' => 'PRV-001']);
        $provider2 = $this->createProvider(['code' => 'PRV-002']);

        $this->createTransaction($provider1);
        $this->createTransaction($provider2);

        $this->assertEquals(1, SaldoTransaction::byProvider($provider1->id)->count());
    }

    /** @test */
    public function transaction_by_product_scope_works()
    {
        $provider = $this->createProvider();
        $product1 = $this->createProduct(['code' => 'PRD-001']);
        $product2 = $this->createProduct(['code' => 'PRD-002']);

        $this->createTransaction($provider, ['saldo_product_id' => $product1->id]);
        $this->createTransaction($provider, ['saldo_product_id' => $product2->id]);

        $this->assertEquals(1, SaldoTransaction::byProduct($product1->id)->count());
    }

    /** @test */
    public function transaction_by_date_range_scope_works()
    {
        $provider = $this->createProvider();
        $this->createTransaction($provider, ['transaction_date' => '2026-01-01']);
        $this->createTransaction($provider, ['transaction_date' => '2026-01-15']);
        $this->createTransaction($provider, ['transaction_date' => '2026-02-01']);

        $this->assertEquals(2, SaldoTransaction::byDateRange('2026-01-01', '2026-01-31')->count());
    }

    // ==================== BUSINESS UNIT SCOPING TESTS ====================

    /** @test */
    public function non_superadmin_provider_list_only_sees_own_unit()
    {
        $unit2 = BusinessUnit::withoutEvents(fn () => BusinessUnit::create(['code' => 'UNT-002', 'name' => 'Unit 2', 'is_active' => true]));

        $this->createProvider(['code' => 'PRV-001', 'name' => 'Unit 1 Provider']);
        SaldoProvider::withoutEvents(fn () => SaldoProvider::create([
            'business_unit_id' => $unit2->id, 'code' => 'PRV-002', 'name' => 'Unit 2 Provider',
            'type' => 'bank', 'initial_balance' => 0, 'current_balance' => 0, 'is_active' => true,
        ]));

        $regularUser = User::withoutEvents(fn () => User::factory()->create(['business_unit_id' => $this->unit->id]));
        $this->actingAs($regularUser);

        Livewire::test(SaldoProviderList::class)
            ->assertSee('Unit 1 Provider')
            ->assertDontSee('Unit 2 Provider');
    }

    /** @test */
    public function non_superadmin_product_list_only_sees_own_unit()
    {
        $unit2 = BusinessUnit::withoutEvents(fn () => BusinessUnit::create(['code' => 'UNT-002', 'name' => 'Unit 2', 'is_active' => true]));

        $this->createProduct(['code' => 'PRD-001', 'name' => 'Unit 1 Prod']);
        SaldoProduct::withoutEvents(fn () => SaldoProduct::create([
            'business_unit_id' => $unit2->id, 'code' => 'PRD-002', 'name' => 'Unit 2 Prod',
            'buy_price' => 100, 'sell_price' => 200, 'is_active' => true,
        ]));

        $regularUser = User::withoutEvents(fn () => User::factory()->create(['business_unit_id' => $this->unit->id]));
        $this->actingAs($regularUser);

        Livewire::test(SaldoProductList::class)
            ->assertSee('Unit 1 Prod')
            ->assertDontSee('Unit 2 Prod');
    }

    /** @test */
    public function non_superadmin_form_auto_fills_business_unit_id()
    {
        $regularUser = User::withoutEvents(fn () => User::factory()->create(['business_unit_id' => $this->unit->id]));
        $this->actingAs($regularUser);

        Livewire::test(SaldoProviderForm::class)
            ->call('openSaldoProviderModal')
            ->assertSet('business_unit_id', $this->unit->id);
    }

    /** @test */
    public function non_superadmin_save_uses_own_business_unit_id()
    {
        $regularUser = User::withoutEvents(fn () => User::factory()->create(['business_unit_id' => $this->unit->id]));
        $this->actingAs($regularUser);

        Livewire::test(SaldoProviderForm::class)
            ->call('openSaldoProviderModal')
            ->set('code', 'PRV-AUTO')
            ->set('name', 'Auto Unit Provider')
            ->set('type', 'e-wallet')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('saldo_providers', [
            'code' => 'PRV-AUTO',
            'business_unit_id' => $this->unit->id,
        ]);
    }

    /** @test */
    public function superadmin_sees_unit_selector_in_provider_form()
    {
        Livewire::test(SaldoProviderForm::class)
            ->call('openSaldoProviderModal')
            ->assertSee('-- Pilih Unit Usaha --');
    }

    /** @test */
    public function non_superadmin_does_not_see_unit_selector_in_provider_form()
    {
        $regularUser = User::withoutEvents(fn () => User::factory()->create(['business_unit_id' => $this->unit->id]));
        $this->actingAs($regularUser);

        Livewire::test(SaldoProviderForm::class)
            ->call('openSaldoProviderModal')
            ->assertDontSee('-- Pilih Unit Usaha --');
    }

    // ==================== BALANCE FLOW INTEGRATION TESTS ====================

    /** @test */
    public function full_balance_flow_topup_then_sell()
    {
        $service = new SaldoService();
        $provider = $this->createProvider(['current_balance' => 0]);

        // Top up 1M
        $service->createTopup([
            'business_unit_id' => $this->unit->id,
            'saldo_provider_id' => $provider->id,
            'amount' => 1000000,
            'fee' => 2500,
            'topup_date' => now()->format('Y-m-d'),
            'method' => 'transfer',
        ]);

        $this->assertEquals(997500, $provider->fresh()->current_balance);

        // Sell Pulsa 50K (cost 49950, Price 52000)
        $tx = $service->createTransaction([
            'business_unit_id' => $this->unit->id,
            'saldo_provider_id' => $provider->id,
            'buy_price' => 49950,
            'sell_price' => 52000,
            'transaction_date' => now()->format('Y-m-d'),
        ]);

        $this->assertEquals(2050, $tx->profit);
        $this->assertEquals(947550, $provider->fresh()->current_balance);
    }

    /** @test */
    public function multiple_topups_accumulate_balance()
    {
        $service = new SaldoService();
        $provider = $this->createProvider(['current_balance' => 0]);

        $service->createTopup([
            'business_unit_id' => $this->unit->id,
            'saldo_provider_id' => $provider->id,
            'amount' => 500000,
            'fee' => 0,
            'topup_date' => now()->format('Y-m-d'),
            'method' => 'cash',
        ]);

        $service->createTopup([
            'business_unit_id' => $this->unit->id,
            'saldo_provider_id' => $provider->id,
            'amount' => 300000,
            'fee' => 1000,
            'topup_date' => now()->format('Y-m-d'),
            'method' => 'transfer',
        ]);

        // 500000 + (300000 - 1000) = 799000
        $this->assertEquals(799000, $provider->fresh()->current_balance);
    }

    /** @test */
    public function multiple_transactions_deduct_balance()
    {
        $service = new SaldoService();
        $provider = $this->createProvider(['current_balance' => 1000000]);

        $service->createTransaction([
            'business_unit_id' => $this->unit->id,
            'saldo_provider_id' => $provider->id,
            'buy_price' => 49950,
            'sell_price' => 52000,
            'transaction_date' => now()->format('Y-m-d'),
        ]);

        $service->createTransaction([
            'business_unit_id' => $this->unit->id,
            'saldo_provider_id' => $provider->id,
            'buy_price' => 97000,
            'sell_price' => 102000,
            'transaction_date' => now()->format('Y-m-d'),
        ]);

        // 1000000 - 49950 - 97000 = 853050
        $this->assertEquals(853050, $provider->fresh()->current_balance);
    }
}
