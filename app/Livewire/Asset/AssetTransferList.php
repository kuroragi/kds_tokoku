<?php

namespace App\Livewire\Asset;

use App\Models\Asset;
use App\Models\AssetTransfer;
use App\Services\BusinessUnitService;
use Livewire\Component;

class AssetTransferList extends Component
{
    public $search = '';
    public $filterUnit = '';
    public $sortField = 'transfer_date';
    public $sortDirection = 'desc';

    protected $listeners = ['refreshAssetTransferList' => '$refresh'];

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

    public function deleteTransfer($id)
    {
        $transfer = AssetTransfer::findOrFail($id);
        $transfer->delete();
        $this->dispatch('alert', type: 'success', message: 'Catatan mutasi berhasil dihapus.');
    }

    public function render()
    {
        $query = AssetTransfer::with(['asset.businessUnit', 'fromBusinessUnit', 'toBusinessUnit']);

        if ($this->search) {
            $query->whereHas('asset', function ($q) {
                $q->where('code', 'like', "%{$this->search}%")
                    ->orWhere('name', 'like', "%{$this->search}%");
            })->orWhere('from_location', 'like', "%{$this->search}%")
              ->orWhere('to_location', 'like', "%{$this->search}%");
        }

        if (!BusinessUnitService::isSuperAdmin()) {
            $unitId = BusinessUnitService::getUserBusinessUnitId();
            if ($unitId) {
                $query->whereHas('asset', fn($q) => $q->where('business_unit_id', $unitId));
            }
        } elseif ($this->filterUnit) {
            $query->whereHas('asset', fn($q) => $q->where('business_unit_id', $this->filterUnit));
        }

        $transfers = $query->orderBy($this->sortField, $this->sortDirection)->get();

        return view('livewire.asset.asset-transfer-list', [
            'transfers' => $transfers,
            'units' => $this->units,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
