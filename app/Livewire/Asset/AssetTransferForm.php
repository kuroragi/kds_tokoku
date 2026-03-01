<?php

namespace App\Livewire\Asset;

use App\Models\Asset;
use App\Models\AssetTransfer;
use App\Services\BusinessUnitService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class AssetTransferForm extends Component
{
    public bool $showModal = false;
    public ?int $transferId = null;
    public bool $isEditing = false;

    public $business_unit_id = '';
    public $asset_id = '';
    public $transfer_date = '';
    public $from_location = '';
    public $to_location = '';
    public $from_business_unit_id = '';
    public $to_business_unit_id = '';
    public $reason = '';
    public $notes = '';

    protected $listeners = ['openAssetTransferModal', 'editAssetTransfer'];

    public function openAssetTransferModal()
    {
        $this->resetForm();
        $this->business_unit_id = BusinessUnitService::getDefaultBusinessUnitId();
        $this->transfer_date = now()->format('Y-m-d');
        $this->showModal = true;
    }

    public function editAssetTransfer($id)
    {
        $transfer = AssetTransfer::with('asset')->findOrFail($id);
        $this->transferId = $transfer->id;
        $this->isEditing = true;
        $this->business_unit_id = $transfer->asset->business_unit_id;
        $this->asset_id = $transfer->asset_id;
        $this->transfer_date = $transfer->transfer_date->format('Y-m-d');
        $this->from_location = $transfer->from_location ?? '';
        $this->to_location = $transfer->to_location ?? '';
        $this->from_business_unit_id = $transfer->from_business_unit_id ?? '';
        $this->to_business_unit_id = $transfer->to_business_unit_id ?? '';
        $this->reason = $transfer->reason ?? '';
        $this->notes = $transfer->notes ?? '';
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->transferId = null;
        $this->isEditing = false;
        $this->business_unit_id = '';
        $this->asset_id = '';
        $this->transfer_date = '';
        $this->from_location = '';
        $this->to_location = '';
        $this->from_business_unit_id = '';
        $this->to_business_unit_id = '';
        $this->reason = '';
        $this->notes = '';
        $this->resetValidation();
    }

    public function updatedAssetId($value)
    {
        if ($value) {
            $asset = Asset::find($value);
            if ($asset) {
                $this->from_location = $asset->location ?? '';
                $this->from_business_unit_id = $asset->business_unit_id;
            }
        }
    }

    protected function rules(): array
    {
        return [
            'asset_id' => 'required|exists:assets,id',
            'transfer_date' => 'required|date',
            'from_location' => 'nullable|string|max:255',
            'to_location' => 'required|string|max:255',
            'from_business_unit_id' => 'nullable|exists:business_units,id',
            'to_business_unit_id' => 'nullable|exists:business_units,id',
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function save()
    {
        $this->validate();

        $data = [
            'asset_id' => $this->asset_id,
            'transfer_date' => $this->transfer_date,
            'from_location' => $this->from_location ?: null,
            'to_location' => $this->to_location,
            'from_business_unit_id' => $this->from_business_unit_id ?: null,
            'to_business_unit_id' => $this->to_business_unit_id ?: null,
            'reason' => $this->reason ?: null,
            'notes' => $this->notes ?: null,
        ];

        DB::beginTransaction();
        try {
            if ($this->isEditing) {
                AssetTransfer::findOrFail($this->transferId)->update($data);
            } else {
                AssetTransfer::create($data);

                // Update lokasi aset
                $asset = Asset::find($this->asset_id);
                if ($asset) {
                    $updateData = ['location' => $this->to_location];
                    if ($this->to_business_unit_id) {
                        $updateData['business_unit_id'] = $this->to_business_unit_id;
                    }
                    $asset->update($updateData);
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->dispatch('alert', type: 'error', message: "Gagal menyimpan mutasi: {$e->getMessage()}");
            return;
        }

        $action = $this->isEditing ? 'diperbarui' : 'dicatat';
        $this->dispatch('alert', type: 'success', message: "Mutasi aset berhasil {$action}.");
        $this->dispatch('refreshAssetTransferList');
        $this->closeModal();
    }

    public function getUnitsProperty()
    {
        return BusinessUnitService::getAvailableUnits();
    }

    public function getAssetsProperty()
    {
        $query = Asset::active();
        if (!BusinessUnitService::isSuperAdmin()) {
            $unitId = BusinessUnitService::getUserBusinessUnitId();
            if ($unitId) $query->byBusinessUnit($unitId);
        } elseif ($this->business_unit_id) {
            $query->byBusinessUnit($this->business_unit_id);
        }
        return $query->orderBy('code')->get();
    }

    public function render()
    {
        return view('livewire.asset.asset-transfer-form', [
            'units' => $this->units,
            'assets' => $this->assets,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
