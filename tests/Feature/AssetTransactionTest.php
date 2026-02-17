<?php

namespace Tests\Feature;

use App\Livewire\Asset\AssetTransferForm;
use App\Livewire\Asset\AssetTransferList;
use App\Livewire\Asset\AssetDisposalForm;
use App\Livewire\Asset\AssetDisposalList;
use App\Livewire\Asset\AssetRepairForm;
use App\Livewire\Asset\AssetRepairList;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetDisposal;
use App\Models\AssetRepair;
use App\Models\AssetTransfer;
use App\Models\BusinessUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AssetTransactionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected BusinessUnit $unit;
    protected AssetCategory $category;
    protected Asset $asset;

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

        $this->asset = Asset::withoutEvents(fn() => Asset::create([
            'business_unit_id' => $this->unit->id,
            'asset_category_id' => $this->category->id,
            'code' => 'AST-001',
            'name' => 'Laptop Asus',
            'acquisition_date' => '2025-01-15',
            'acquisition_cost' => 12000000,
            'useful_life_months' => 60,
            'salvage_value' => 1000000,
            'depreciation_method' => 'straight_line',
            'location' => 'Ruang IT',
            'condition' => 'good',
            'status' => 'active',
        ]));
    }

    // ==================== TRANSFER: PAGE ACCESS TESTS ====================

    /** @test */
    public function asset_transfer_page_is_accessible()
    {
        $response = $this->get(route('asset-transfer.index'));
        $response->assertStatus(200);
        $response->assertSee('Mutasi');
    }

    /** @test */
    public function guest_cannot_access_asset_transfer_page()
    {
        auth()->logout();
        $this->get(route('asset-transfer.index'))->assertRedirect(route('login'));
    }

    // ==================== TRANSFER: LIST TESTS ====================

    /** @test */
    public function asset_transfer_list_renders_successfully()
    {
        Livewire::test(AssetTransferList::class)->assertStatus(200);
    }

    /** @test */
    public function asset_transfer_list_shows_transfers()
    {
        AssetTransfer::withoutEvents(fn() => AssetTransfer::create([
            'asset_id' => $this->asset->id,
            'transfer_date' => '2025-06-01',
            'from_location' => 'Ruang IT',
            'to_location' => 'Ruang Marketing',
        ]));

        Livewire::test(AssetTransferList::class)
            ->assertSee('Ruang Marketing');
    }

    /** @test */
    public function asset_transfer_can_be_deleted()
    {
        $transfer = AssetTransfer::withoutEvents(fn() => AssetTransfer::create([
            'asset_id' => $this->asset->id,
            'transfer_date' => '2025-06-01',
            'from_location' => 'Ruang IT',
            'to_location' => 'Ruang HRD',
        ]));

        Livewire::test(AssetTransferList::class)
            ->call('deleteTransfer', $transfer->id)
            ->assertDispatched('alert');

        $this->assertSoftDeleted('asset_transfers', ['id' => $transfer->id]);
    }

    // ==================== TRANSFER: FORM TESTS ====================

    /** @test */
    public function asset_transfer_form_renders_successfully()
    {
        Livewire::test(AssetTransferForm::class)->assertStatus(200);
    }

    /** @test */
    public function asset_transfer_form_can_open_modal()
    {
        Livewire::test(AssetTransferForm::class)
            ->call('openAssetTransferModal')
            ->assertSet('showModal', true)
            ->assertSet('isEditing', false);
    }

    /** @test */
    public function asset_transfer_form_can_create_transfer()
    {
        Livewire::test(AssetTransferForm::class)
            ->call('openAssetTransferModal')
            ->set('asset_id', $this->asset->id)
            ->set('transfer_date', '2025-06-15')
            ->set('from_location', 'Ruang IT')
            ->set('to_location', 'Gudang')
            ->call('save')
            ->assertDispatched('alert', type: 'success')
            ->assertDispatched('refreshAssetTransferList')
            ->assertSet('showModal', false);

        $this->assertDatabaseHas('asset_transfers', [
            'asset_id' => $this->asset->id,
            'to_location' => 'Gudang',
        ]);

        // Check asset location updated
        $this->assertEquals('Gudang', $this->asset->fresh()->location);
    }

    /** @test */
    public function asset_transfer_updates_business_unit_when_provided()
    {
        $unit2 = BusinessUnit::withoutEvents(fn() =>
            BusinessUnit::create(['code' => 'UNT-002', 'name' => 'Unit 2', 'is_active' => true])
        );

        Livewire::test(AssetTransferForm::class)
            ->call('openAssetTransferModal')
            ->set('asset_id', $this->asset->id)
            ->set('transfer_date', '2025-06-15')
            ->set('to_location', 'Unit 2 Office')
            ->set('to_business_unit_id', $unit2->id)
            ->call('save')
            ->assertDispatched('alert', type: 'success');

        $this->assertEquals($unit2->id, $this->asset->fresh()->business_unit_id);
    }

    /** @test */
    public function asset_transfer_form_can_edit_transfer()
    {
        $transfer = AssetTransfer::withoutEvents(fn() => AssetTransfer::create([
            'asset_id' => $this->asset->id,
            'transfer_date' => '2025-06-01',
            'from_location' => 'Ruang IT',
            'to_location' => 'Gudang',
        ]));

        Livewire::test(AssetTransferForm::class)
            ->call('editAssetTransfer', $transfer->id)
            ->assertSet('showModal', true)
            ->assertSet('isEditing', true)
            ->set('to_location', 'Ruang Marketing')
            ->call('save')
            ->assertDispatched('alert', type: 'success');

        $this->assertDatabaseHas('asset_transfers', ['id' => $transfer->id, 'to_location' => 'Ruang Marketing']);
    }

    /** @test */
    public function asset_transfer_form_validates_required_fields()
    {
        Livewire::test(AssetTransferForm::class)
            ->call('openAssetTransferModal')
            ->set('asset_id', '')
            ->set('transfer_date', '')
            ->set('to_location', '')
            ->call('save')
            ->assertHasErrors(['asset_id', 'transfer_date', 'to_location']);
    }

    /** @test */
    public function asset_transfer_form_auto_fills_from_location()
    {
        Livewire::test(AssetTransferForm::class)
            ->call('openAssetTransferModal')
            ->set('asset_id', $this->asset->id)
            ->assertSet('from_location', 'Ruang IT');
    }

    // ==================== DISPOSAL: PAGE ACCESS TESTS ====================

    /** @test */
    public function asset_disposal_page_is_accessible()
    {
        $response = $this->get(route('asset-disposal.index'));
        $response->assertStatus(200);
        $response->assertSee('Disposal');
    }

    /** @test */
    public function guest_cannot_access_asset_disposal_page()
    {
        auth()->logout();
        $this->get(route('asset-disposal.index'))->assertRedirect(route('login'));
    }

    // ==================== DISPOSAL: LIST TESTS ====================

    /** @test */
    public function asset_disposal_list_renders_successfully()
    {
        Livewire::test(AssetDisposalList::class)->assertStatus(200);
    }

    /** @test */
    public function asset_disposal_list_shows_disposals()
    {
        AssetDisposal::withoutEvents(fn() => AssetDisposal::create([
            'asset_id' => $this->asset->id,
            'disposal_date' => '2025-06-01',
            'disposal_method' => 'sold',
            'disposal_amount' => 5000000,
            'book_value_at_disposal' => 10000000,
            'gain_loss' => -5000000,
        ]));

        Livewire::test(AssetDisposalList::class)
            ->assertSee('Laptop Asus');
    }

    /** @test */
    public function asset_disposal_can_be_deleted_and_restores_asset()
    {
        $this->asset->update(['status' => 'disposed']);

        $disposal = AssetDisposal::withoutEvents(fn() => AssetDisposal::create([
            'asset_id' => $this->asset->id,
            'disposal_date' => '2025-06-01',
            'disposal_method' => 'scrapped',
            'disposal_amount' => 0,
            'book_value_at_disposal' => 10000000,
            'gain_loss' => -10000000,
        ]));

        Livewire::test(AssetDisposalList::class)
            ->call('deleteDisposal', $disposal->id)
            ->assertDispatched('alert');

        $this->assertSoftDeleted('asset_disposals', ['id' => $disposal->id]);
        $this->assertEquals('active', $this->asset->fresh()->status);
    }

    /** @test */
    public function asset_disposal_list_can_filter_by_method()
    {
        AssetDisposal::withoutEvents(fn() => AssetDisposal::create([
            'asset_id' => $this->asset->id,
            'disposal_date' => '2025-06-01',
            'disposal_method' => 'sold',
            'disposal_amount' => 5000000,
            'book_value_at_disposal' => 10000000,
            'gain_loss' => -5000000,
        ]));

        $asset2 = Asset::withoutEvents(fn() => Asset::create([
            'business_unit_id' => $this->unit->id,
            'asset_category_id' => $this->category->id,
            'code' => 'AST-002',
            'name' => 'Printer',
            'acquisition_date' => '2025-01-15',
            'acquisition_cost' => 5000000,
            'useful_life_months' => 60,
            'salvage_value' => 0,
            'depreciation_method' => 'straight_line',
            'condition' => 'poor',
            'status' => 'disposed',
        ]));

        AssetDisposal::withoutEvents(fn() => AssetDisposal::create([
            'asset_id' => $asset2->id,
            'disposal_date' => '2025-06-02',
            'disposal_method' => 'scrapped',
            'disposal_amount' => 0,
            'book_value_at_disposal' => 5000000,
            'gain_loss' => -5000000,
        ]));

        Livewire::test(AssetDisposalList::class)
            ->set('filterMethod', 'sold')
            ->assertSee('Laptop Asus')
            ->assertDontSee('Printer');
    }

    // ==================== DISPOSAL: FORM TESTS ====================

    /** @test */
    public function asset_disposal_form_renders_successfully()
    {
        Livewire::test(AssetDisposalForm::class)->assertStatus(200);
    }

    /** @test */
    public function asset_disposal_form_can_open_modal()
    {
        Livewire::test(AssetDisposalForm::class)
            ->call('openAssetDisposalModal')
            ->assertSet('showModal', true)
            ->assertSet('isEditing', false);
    }

    /** @test */
    public function asset_disposal_form_can_create_disposal()
    {
        Livewire::test(AssetDisposalForm::class)
            ->call('openAssetDisposalModal')
            ->set('asset_id', $this->asset->id)
            ->set('disposal_date', '2025-06-15')
            ->set('disposal_method', 'sold')
            ->set('disposal_amount', 5000000)
            ->set('buyer_info', 'PT Pembeli')
            ->call('save')
            ->assertDispatched('alert', type: 'success')
            ->assertDispatched('refreshAssetDisposalList')
            ->assertSet('showModal', false);

        $this->assertDatabaseHas('asset_disposals', [
            'asset_id' => $this->asset->id,
            'disposal_method' => 'sold',
            'disposal_amount' => 5000000,
            'buyer_info' => 'PT Pembeli',
        ]);

        // Asset status should be disposed
        $this->assertEquals('disposed', $this->asset->fresh()->status);
    }

    /** @test */
    public function asset_disposal_form_validates_required_fields()
    {
        Livewire::test(AssetDisposalForm::class)
            ->call('openAssetDisposalModal')
            ->set('asset_id', '')
            ->set('disposal_date', '')
            ->set('disposal_method', '')
            ->call('save')
            ->assertHasErrors(['asset_id', 'disposal_date', 'disposal_method']);
    }

    /** @test */
    public function asset_disposal_form_validates_method_enum()
    {
        Livewire::test(AssetDisposalForm::class)
            ->call('openAssetDisposalModal')
            ->set('asset_id', $this->asset->id)
            ->set('disposal_date', '2025-06-01')
            ->set('disposal_method', 'invalid')
            ->call('save')
            ->assertHasErrors(['disposal_method']);
    }

    /** @test */
    public function asset_disposal_form_calculates_gain_loss()
    {
        Livewire::test(AssetDisposalForm::class)
            ->call('openAssetDisposalModal')
            ->set('asset_id', $this->asset->id)
            ->set('disposal_amount', 15000000)
            ->assertSet('gain_loss', 15000000 - 12000000); // gain = disposal - book_value
    }

    /** @test */
    public function asset_disposal_has_methods_constant()
    {
        $methods = AssetDisposal::METHODS;
        $this->assertArrayHasKey('sold', $methods);
        $this->assertArrayHasKey('scrapped', $methods);
        $this->assertArrayHasKey('donated', $methods);
    }

    // ==================== REPAIR: PAGE ACCESS TESTS ====================

    /** @test */
    public function asset_repair_page_is_accessible()
    {
        $response = $this->get(route('asset-repair.index'));
        $response->assertStatus(200);
        $response->assertSee('Perbaikan');
    }

    /** @test */
    public function guest_cannot_access_asset_repair_page()
    {
        auth()->logout();
        $this->get(route('asset-repair.index'))->assertRedirect(route('login'));
    }

    // ==================== REPAIR: LIST TESTS ====================

    /** @test */
    public function asset_repair_list_renders_successfully()
    {
        Livewire::test(AssetRepairList::class)->assertStatus(200);
    }

    /** @test */
    public function asset_repair_list_shows_repairs()
    {
        AssetRepair::withoutEvents(fn() => AssetRepair::create([
            'asset_id' => $this->asset->id,
            'repair_date' => '2025-06-01',
            'description' => 'Ganti baterai',
            'cost' => 500000,
            'status' => 'pending',
        ]));

        Livewire::test(AssetRepairList::class)
            ->assertSee('Ganti baterai');
    }

    /** @test */
    public function asset_repair_list_can_filter_by_status()
    {
        AssetRepair::withoutEvents(fn() => AssetRepair::create([
            'asset_id' => $this->asset->id,
            'repair_date' => '2025-06-01',
            'description' => 'Pending Repair',
            'cost' => 500000,
            'status' => 'pending',
        ]));

        $asset2 = Asset::withoutEvents(fn() => Asset::create([
            'business_unit_id' => $this->unit->id,
            'asset_category_id' => $this->category->id,
            'code' => 'AST-002',
            'name' => 'Printer',
            'acquisition_date' => '2025-01-15',
            'acquisition_cost' => 5000000,
            'useful_life_months' => 60,
            'salvage_value' => 0,
            'depreciation_method' => 'straight_line',
            'condition' => 'fair',
            'status' => 'active',
        ]));

        AssetRepair::withoutEvents(fn() => AssetRepair::create([
            'asset_id' => $asset2->id,
            'repair_date' => '2025-06-02',
            'description' => 'Completed Repair',
            'cost' => 300000,
            'status' => 'completed',
            'completed_date' => '2025-06-05',
        ]));

        Livewire::test(AssetRepairList::class)
            ->set('filterStatus', 'pending')
            ->assertSee('Pending Repair')
            ->assertDontSee('Completed Repair');
    }

    /** @test */
    public function asset_repair_can_be_deleted()
    {
        $repair = AssetRepair::withoutEvents(fn() => AssetRepair::create([
            'asset_id' => $this->asset->id,
            'repair_date' => '2025-06-01',
            'description' => 'Test Repair',
            'cost' => 500000,
            'status' => 'pending',
        ]));

        Livewire::test(AssetRepairList::class)
            ->call('deleteRepair', $repair->id)
            ->assertDispatched('alert');

        $this->assertSoftDeleted('asset_repairs', ['id' => $repair->id]);
    }

    /** @test */
    public function asset_repair_status_can_be_updated_to_completed()
    {
        $this->asset->update(['status' => 'under_repair']);

        $repair = AssetRepair::withoutEvents(fn() => AssetRepair::create([
            'asset_id' => $this->asset->id,
            'repair_date' => '2025-06-01',
            'description' => 'In Progress Repair',
            'cost' => 500000,
            'status' => 'in_progress',
        ]));

        Livewire::test(AssetRepairList::class)
            ->call('updateRepairStatus', $repair->id, 'completed')
            ->assertDispatched('alert');

        $this->assertEquals('completed', $repair->fresh()->status);
        $this->assertNotNull($repair->fresh()->completed_date);
        // Asset should be restored to active
        $this->assertEquals('active', $this->asset->fresh()->status);
    }

    // ==================== REPAIR: FORM TESTS ====================

    /** @test */
    public function asset_repair_form_renders_successfully()
    {
        Livewire::test(AssetRepairForm::class)->assertStatus(200);
    }

    /** @test */
    public function asset_repair_form_can_open_modal()
    {
        Livewire::test(AssetRepairForm::class)
            ->call('openAssetRepairModal')
            ->assertSet('showModal', true)
            ->assertSet('isEditing', false);
    }

    /** @test */
    public function asset_repair_form_can_create_repair()
    {
        Livewire::test(AssetRepairForm::class)
            ->call('openAssetRepairModal')
            ->set('asset_id', $this->asset->id)
            ->set('repair_date', '2025-06-15')
            ->set('description', 'Ganti layar')
            ->set('cost', 2000000)
            ->set('status', 'pending')
            ->call('save')
            ->assertDispatched('alert', type: 'success')
            ->assertDispatched('refreshAssetRepairList')
            ->assertSet('showModal', false);

        $this->assertDatabaseHas('asset_repairs', [
            'asset_id' => $this->asset->id,
            'description' => 'Ganti layar',
            'cost' => 2000000,
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function asset_repair_form_marks_asset_under_repair()
    {
        Livewire::test(AssetRepairForm::class)
            ->call('openAssetRepairModal')
            ->set('asset_id', $this->asset->id)
            ->set('repair_date', '2025-06-15')
            ->set('description', 'Major repair')
            ->set('cost', 3000000)
            ->set('status', 'pending')
            ->set('mark_under_repair', true)
            ->call('save')
            ->assertDispatched('alert', type: 'success');

        $this->assertEquals('under_repair', $this->asset->fresh()->status);
    }

    /** @test */
    public function asset_repair_form_can_edit_repair()
    {
        $repair = AssetRepair::withoutEvents(fn() => AssetRepair::create([
            'asset_id' => $this->asset->id,
            'repair_date' => '2025-06-01',
            'description' => 'Test Repair',
            'cost' => 500000,
            'status' => 'pending',
        ]));

        Livewire::test(AssetRepairForm::class)
            ->call('editAssetRepair', $repair->id)
            ->assertSet('showModal', true)
            ->assertSet('isEditing', true)
            ->set('description', 'Updated Repair')
            ->set('cost', 750000)
            ->call('save')
            ->assertDispatched('alert', type: 'success');

        $this->assertDatabaseHas('asset_repairs', [
            'id' => $repair->id,
            'description' => 'Updated Repair',
            'cost' => 750000,
        ]);
    }

    /** @test */
    public function asset_repair_completed_restores_asset_status()
    {
        $this->asset->update(['status' => 'under_repair']);

        $repair = AssetRepair::withoutEvents(fn() => AssetRepair::create([
            'asset_id' => $this->asset->id,
            'repair_date' => '2025-06-01',
            'description' => 'Test Repair',
            'cost' => 500000,
            'status' => 'in_progress',
        ]));

        Livewire::test(AssetRepairForm::class)
            ->call('editAssetRepair', $repair->id)
            ->set('status', 'completed')
            ->call('save')
            ->assertDispatched('alert', type: 'success');

        $this->assertEquals('active', $this->asset->fresh()->status);
    }

    /** @test */
    public function asset_repair_form_validates_required_fields()
    {
        Livewire::test(AssetRepairForm::class)
            ->call('openAssetRepairModal')
            ->set('asset_id', '')
            ->set('repair_date', '')
            ->set('description', '')
            ->call('save')
            ->assertHasErrors(['asset_id', 'repair_date', 'description']);
    }

    /** @test */
    public function asset_repair_has_statuses_constant()
    {
        $statuses = AssetRepair::STATUSES;
        $this->assertArrayHasKey('pending', $statuses);
        $this->assertArrayHasKey('in_progress', $statuses);
        $this->assertArrayHasKey('completed', $statuses);
    }
}
