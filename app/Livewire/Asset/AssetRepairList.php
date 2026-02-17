<?php

namespace App\Livewire\Asset;

use App\Models\AssetRepair;
use App\Services\BusinessUnitService;
use Livewire\Component;

class AssetRepairList extends Component
{
    public $search = '';
    public $filterUnit = '';
    public $filterStatus = '';
    public $sortField = 'repair_date';
    public $sortDirection = 'desc';

    protected $listeners = ['refreshAssetRepairList' => '$refresh'];

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function getUnitsProperty()
    {
        return BusinessUnitService::getAvailableUnits();
    }

    public function deleteRepair($id)
    {
        $repair = AssetRepair::findOrFail($id);
        $repair->delete();
        $this->dispatch('alert', type: 'success', message: 'Catatan perbaikan berhasil dihapus.');
    }

    public function updateRepairStatus($id, $status)
    {
        $repair = AssetRepair::findOrFail($id);
        $updateData = ['status' => $status];

        if ($status === 'completed') {
            $updateData['completed_date'] = now()->format('Y-m-d');
            // Restore asset status if it was under_repair
            if ($repair->asset && $repair->asset->status === 'under_repair') {
                $repair->asset->update(['status' => 'active']);
            }
        }

        $repair->update($updateData);
        $statusLabel = AssetRepair::STATUSES[$status] ?? $status;
        $this->dispatch('alert', type: 'success', message: "Status perbaikan diubah menjadi '{$statusLabel}'.");
    }

    public function render()
    {
        $query = AssetRepair::with(['asset.businessUnit', 'vendor']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->whereHas('asset', function ($aq) {
                    $aq->where('code', 'like', "%{$this->search}%")
                        ->orWhere('name', 'like', "%{$this->search}%");
                })->orWhere('description', 'like', "%{$this->search}%");
            });
        }

        if (!BusinessUnitService::isSuperAdmin()) {
            $unitId = BusinessUnitService::getUserBusinessUnitId();
            if ($unitId) {
                $query->whereHas('asset', fn($q) => $q->where('business_unit_id', $unitId));
            }
        } elseif ($this->filterUnit) {
            $query->whereHas('asset', fn($q) => $q->where('business_unit_id', $this->filterUnit));
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        $repairs = $query->orderBy($this->sortField, $this->sortDirection)->get();

        return view('livewire.asset.asset-repair-list', [
            'repairs' => $repairs,
            'units' => $this->units,
            'statuses' => AssetRepair::STATUSES,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
