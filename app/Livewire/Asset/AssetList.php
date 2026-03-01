<?php

namespace App\Livewire\Asset;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Services\AssetService;
use App\Services\BusinessUnitService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class AssetList extends Component
{
    public $search = '';
    public $filterUnit = '';
    public $filterCategory = '';
    public $filterStatus = '';
    public $sortField = 'code';
    public $sortDirection = 'asc';

    protected $listeners = ['refreshAssetList' => '$refresh', 'retryJournal' => 'retryJournal'];

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

    public function getCategoriesProperty()
    {
        $query = AssetCategory::active();
        if (!BusinessUnitService::isSuperAdmin()) {
            $unitId = BusinessUnitService::getUserBusinessUnitId();
            if ($unitId) $query->byBusinessUnit($unitId);
        } elseif ($this->filterUnit) {
            $query->byBusinessUnit($this->filterUnit);
        }
        return $query->orderBy('name')->get();
    }

    /**
     * Retry pembuatan jurnal untuk aset yang gagal/belum punya jurnal.
     */
    public function retryJournal(int $id)
    {
        $asset = Asset::with('assetCategory')->findOrFail($id);

        if ($asset->journal_master_id) {
            $this->dispatch('alert', type: 'info', message: "Aset '{$asset->name}' sudah memiliki jurnal.");
            return;
        }

        if (!$asset->assetCategory?->coa_asset_key) {
            $this->dispatch('alert', type: 'error', message: "Kategori '{$asset->assetCategory?->name}' belum memiliki mapping COA. Atur di menu Kategori Aset.");
            return;
        }

        try {
            $service = app(AssetService::class);
            $paymentKey = $asset->acquisition_type === 'purchase_cash' ? 'kas_utama' : null;
            $service->createAcquisitionJournal($asset, $paymentKey);
            $this->dispatch('alert', type: 'success', message: "Jurnal pengadaan aset '{$asset->name}' berhasil dibuat.");
        } catch (\Throwable $e) {
            $this->dispatch('alert', type: 'error', message: "Gagal membuat jurnal: {$e->getMessage()}");
        }
    }

    public function deleteAsset($id)
    {
        $asset = Asset::findOrFail($id);

        if ($asset->depreciations()->count() > 0) {
            $this->dispatch('alert', type: 'error', message: "Aset '{$asset->name}' tidak bisa dihapus karena sudah memiliki catatan penyusutan.");
            return;
        }

        DB::beginTransaction();
        try {
            // Hapus jurnal pengadaan jika ada
            if ($asset->journal_master_id) {
                $asset->journalMaster?->journals()?->delete();
                $asset->journalMaster?->delete();
            }

            $name = $asset->name;
            $asset->delete();
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->dispatch('alert', type: 'error', message: "Gagal menghapus aset: {$e->getMessage()}");
            return;
        }

        $this->dispatch('alert', type: 'success', message: "Aset '{$name}' berhasil dihapus.");
    }

    public function render()
    {
        $query = Asset::with(['businessUnit', 'assetCategory', 'vendor']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('code', 'like', "%{$this->search}%")
                    ->orWhere('name', 'like', "%{$this->search}%")
                    ->orWhere('serial_number', 'like', "%{$this->search}%")
                    ->orWhere('location', 'like', "%{$this->search}%");
            });
        }

        if (!BusinessUnitService::isSuperAdmin()) {
            $unitId = BusinessUnitService::getUserBusinessUnitId();
            if ($unitId) $query->byBusinessUnit($unitId);
        } elseif ($this->filterUnit) {
            $query->byBusinessUnit($this->filterUnit);
        }

        if ($this->filterCategory) {
            $query->byCategory($this->filterCategory);
        }

        if ($this->filterStatus) {
            $query->byStatus($this->filterStatus);
        }

        $assets = $query->orderBy($this->sortField, $this->sortDirection)->get();

        return view('livewire.asset.asset-list', [
            'assets' => $assets,
            'units' => $this->units,
            'categories' => $this->categories,
            'statuses' => Asset::STATUSES,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
