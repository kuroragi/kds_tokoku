<?php

namespace App\Livewire\Asset;

use App\Models\Asset;
use App\Models\AssetRepair;
use App\Models\Vendor;
use App\Services\BusinessUnitService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class AssetRepairForm extends Component
{
    public bool $showModal = false;
    public ?int $repairId = null;
    public bool $isEditing = false;

    public $business_unit_id = '';
    public $asset_id = '';
    public $vendor_id = '';
    public $repair_date = '';
    public $description = '';
    public $cost = 0;
    public $status = 'pending';
    public $completed_date = '';
    public $notes = '';
    public $mark_under_repair = false;

    protected $listeners = ['openAssetRepairModal', 'editAssetRepair'];

    public function openAssetRepairModal()
    {
        $this->resetForm();
        $this->business_unit_id = BusinessUnitService::getDefaultBusinessUnitId();
        $this->repair_date = now()->format('Y-m-d');
        $this->showModal = true;
    }

    public function editAssetRepair($id)
    {
        $repair = AssetRepair::with('asset')->findOrFail($id);
        $this->repairId = $repair->id;
        $this->isEditing = true;
        $this->business_unit_id = $repair->asset->business_unit_id;
        $this->asset_id = $repair->asset_id;
        $this->vendor_id = $repair->vendor_id ?? '';
        $this->repair_date = $repair->repair_date->format('Y-m-d');
        $this->description = $repair->description ?? '';
        $this->cost = $repair->cost;
        $this->status = $repair->status;
        $this->completed_date = $repair->completed_date ? $repair->completed_date->format('Y-m-d') : '';
        $this->notes = $repair->notes ?? '';
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->repairId = null;
        $this->isEditing = false;
        $this->business_unit_id = '';
        $this->asset_id = '';
        $this->vendor_id = '';
        $this->repair_date = '';
        $this->description = '';
        $this->cost = 0;
        $this->status = 'pending';
        $this->completed_date = '';
        $this->notes = '';
        $this->mark_under_repair = false;
        $this->resetValidation();
    }

    protected function rules(): array
    {
        return [
            'asset_id' => 'required|exists:assets,id',
            'vendor_id' => 'nullable|exists:vendors,id',
            'repair_date' => 'required|date',
            'description' => 'required|string|max:1000',
            'cost' => 'required|integer|min:0',
            'status' => 'required|in:pending,in_progress,completed',
            'completed_date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function save()
    {
        $this->validate();

        $data = [
            'asset_id' => $this->asset_id,
            'vendor_id' => $this->vendor_id ?: null,
            'repair_date' => $this->repair_date,
            'description' => $this->description,
            'cost' => $this->cost,
            'status' => $this->status,
            'completed_date' => $this->status === 'completed' ? ($this->completed_date ?: now()->format('Y-m-d')) : null,
            'notes' => $this->notes ?: null,
        ];

        DB::beginTransaction();
        try {
            if ($this->isEditing) {
                $repair = AssetRepair::findOrFail($this->repairId);
                $repair->update($data);

                if ($this->status === 'completed' && $repair->asset->status === 'under_repair') {
                    $repair->asset->update(['status' => 'active']);
                }
            } else {
                AssetRepair::create($data);

                if ($this->mark_under_repair) {
                    Asset::find($this->asset_id)?->update(['status' => 'under_repair']);
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->dispatch('alert', type: 'error', message: "Gagal menyimpan perbaikan: {$e->getMessage()}");
            return;
        }

        $action = $this->isEditing ? 'diperbarui' : 'dicatat';
        $this->dispatch('alert', type: 'success', message: "Perbaikan aset berhasil {$action}.");
        $this->dispatch('refreshAssetRepairList');
        $this->closeModal();
    }

    public function getUnitsProperty()
    {
        return BusinessUnitService::getAvailableUnits();
    }

    public function getAssetsProperty()
    {
        $query = Asset::query()->whereIn('status', ['active', 'under_repair']);
        if (!BusinessUnitService::isSuperAdmin()) {
            $unitId = BusinessUnitService::getUserBusinessUnitId();
            if ($unitId) $query->byBusinessUnit($unitId);
        } elseif ($this->business_unit_id) {
            $query->byBusinessUnit($this->business_unit_id);
        }
        return $query->orderBy('code')->get();
    }

    public function getVendorsProperty()
    {
        if (!BusinessUnitService::isSuperAdmin()) {
            $unitId = BusinessUnitService::getUserBusinessUnitId();
            return $unitId ? Vendor::active()->byBusinessUnit($unitId)->orderBy('name')->get() : collect();
        }
        return Vendor::active()->orderBy('name')->get();
    }

    public function render()
    {
        return view('livewire.asset.asset-repair-form', [
            'units' => $this->units,
            'assets' => $this->assets,
            'vendors' => $this->vendors,
            'repairStatuses' => AssetRepair::STATUSES,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
