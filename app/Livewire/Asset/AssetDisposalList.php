<?php

namespace App\Livewire\Asset;

use App\Models\AssetDisposal;
use App\Services\BusinessUnitService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class AssetDisposalList extends Component
{
    public $search = '';
    public $filterUnit = '';
    public $filterMethod = '';
    public $sortField = 'disposal_date';
    public $sortDirection = 'desc';

    protected $listeners = ['refreshAssetDisposalList' => '$refresh'];

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

    public function deleteDisposal($id)
    {
        $disposal = AssetDisposal::with('asset')->findOrFail($id);
        $asset = $disposal->asset;

        DB::beginTransaction();
        try {
            // Restore asset status back to active
            if ($asset && $asset->status === 'disposed') {
                $asset->update(['status' => 'active']);
            }

            $disposal->delete();
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->dispatch('alert', type: 'error', message: "Gagal menghapus disposal: {$e->getMessage()}");
            return;
        }

        $this->dispatch('alert', type: 'success', message: 'Catatan disposal berhasil dihapus dan aset dikembalikan.');
    }

    public function render()
    {
        $query = AssetDisposal::with(['asset.businessUnit', 'asset.assetCategory', 'journalMaster']);

        if ($this->search) {
            $query->whereHas('asset', function ($q) {
                $q->where('code', 'like', "%{$this->search}%")
                    ->orWhere('name', 'like', "%{$this->search}%");
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

        if ($this->filterMethod) {
            $query->where('disposal_method', $this->filterMethod);
        }

        $disposals = $query->orderBy($this->sortField, $this->sortDirection)->get();

        return view('livewire.asset.asset-disposal-list', [
            'disposals' => $disposals,
            'units' => $this->units,
            'methods' => AssetDisposal::METHODS,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
