<?php

namespace App\Livewire\Asset\Report;

use App\Models\Asset;
use App\Services\AssetService;
use App\Services\BusinessUnitService;
use Livewire\Component;

class AssetHistoryReport extends Component
{
    public $filterUnit = '';
    public $asset_id = '';
    public $assetDetail = null;
    public $timeline = [];

    public function getUnitsProperty()
    {
        return BusinessUnitService::getAvailableUnits();
    }

    public function getAssetsProperty()
    {
        $query = Asset::query();
        if (!BusinessUnitService::isSuperAdmin()) {
            $unitId = BusinessUnitService::getUserBusinessUnitId();
            if ($unitId) $query->byBusinessUnit($unitId);
        } elseif ($this->filterUnit) {
            $query->byBusinessUnit($this->filterUnit);
        }
        return $query->orderBy('code')->get();
    }

    public function updatedFilterUnit()
    {
        $this->asset_id = '';
        $this->assetDetail = null;
        $this->timeline = [];
    }

    public function updatedAssetId($value)
    {
        if (!$value) {
            $this->assetDetail = null;
            $this->timeline = [];
            return;
        }

        $this->generateHistory();
    }

    public function generateHistory()
    {
        if (!$this->asset_id) return;

        $asset = Asset::with([
            'businessUnit', 'assetCategory', 'vendor',
            'depreciations.period', 'transfers.fromBusinessUnit', 'transfers.toBusinessUnit',
            'repairs.vendor', 'disposals',
        ])->find($this->asset_id);

        if (!$asset) return;

        $service = app(AssetService::class);
        $this->assetDetail = [
            'code' => $asset->code,
            'name' => $asset->name,
            'category' => $asset->assetCategory->name ?? '-',
            'acquisition_date' => $asset->acquisition_date->format('d/m/Y'),
            'acquisition_cost' => $asset->acquisition_cost,
            'book_value' => $service->getCurrentBookValue($asset),
            'accumulated' => $service->getAccumulatedDepreciation($asset),
            'status' => $asset->status,
            'condition' => $asset->condition,
            'location' => $asset->location ?? '-',
            'serial_number' => $asset->serial_number ?? '-',
            'vendor' => $asset->vendor->name ?? '-',
            'salvage_value' => $asset->salvage_value,
            'useful_life_months' => $asset->useful_life_months,
            'depreciation_method' => $asset->depreciation_method,
        ];

        $timeline = collect();

        // 1. Pengadaan
        $timeline->push([
            'date' => $asset->acquisition_date->format('Y-m-d'),
            'type' => 'acquisition',
            'icon' => 'ri-shopping-cart-line',
            'color' => 'primary',
            'title' => 'Pengadaan Aset',
            'description' => 'Pembelian aset senilai Rp ' . number_format($asset->acquisition_cost, 0, ',', '.'),
        ]);

        // 2. Penyusutan
        foreach ($asset->depreciations as $dep) {
            $timeline->push([
                'date' => $dep->depreciation_date->format('Y-m-d'),
                'type' => 'depreciation',
                'icon' => 'ri-line-chart-line',
                'color' => 'warning',
                'title' => 'Penyusutan - ' . ($dep->period->period_name ?? '-'),
                'description' => 'Penyusutan Rp ' . number_format($dep->depreciation_amount, 0, ',', '.') .
                    ' | Akumulasi: Rp ' . number_format($dep->accumulated_depreciation, 0, ',', '.') .
                    ' | Nilai Buku: Rp ' . number_format($dep->book_value, 0, ',', '.'),
            ]);
        }

        // 3. Mutasi
        foreach ($asset->transfers as $transfer) {
            $from = $transfer->from_location ?? ($transfer->fromBusinessUnit->name ?? '-');
            $to = $transfer->to_location ?? ($transfer->toBusinessUnit->name ?? '-');
            $timeline->push([
                'date' => $transfer->transfer_date->format('Y-m-d'),
                'type' => 'transfer',
                'icon' => 'ri-arrow-left-right-line',
                'color' => 'info',
                'title' => 'Mutasi Lokasi',
                'description' => "Dari: {$from} → Ke: {$to}" . ($transfer->reason ? " ({$transfer->reason})" : ''),
            ]);
        }

        // 4. Perbaikan
        foreach ($asset->repairs as $repair) {
            $statusLabel = \App\Models\AssetRepair::STATUSES[$repair->status] ?? $repair->status;
            $timeline->push([
                'date' => $repair->repair_date->format('Y-m-d'),
                'type' => 'repair',
                'icon' => 'ri-tools-line',
                'color' => 'secondary',
                'title' => 'Perbaikan (' . $statusLabel . ')',
                'description' => $repair->description .
                    ' | Biaya: Rp ' . number_format($repair->cost, 0, ',', '.') .
                    ($repair->vendor ? ' | Vendor: ' . $repair->vendor->name : ''),
            ]);
        }

        // 5. Disposal
        foreach ($asset->disposals as $disposal) {
            $methodLabel = \App\Models\AssetDisposal::METHODS[$disposal->disposal_method] ?? $disposal->disposal_method;
            $gainLossLabel = $disposal->gain_loss >= 0
                ? 'Laba: Rp ' . number_format($disposal->gain_loss, 0, ',', '.')
                : 'Rugi: Rp ' . number_format(abs($disposal->gain_loss), 0, ',', '.');
            $timeline->push([
                'date' => $disposal->disposal_date->format('Y-m-d'),
                'type' => 'disposal',
                'icon' => 'ri-delete-bin-line',
                'color' => 'danger',
                'title' => 'Disposal (' . $methodLabel . ')',
                'description' => 'Nilai Disposal: Rp ' . number_format($disposal->disposal_amount, 0, ',', '.') .
                    ' | Nilai Buku: Rp ' . number_format($disposal->book_value_at_disposal, 0, ',', '.') .
                    ' | ' . $gainLossLabel,
            ]);
        }

        $this->timeline = $timeline->sortBy('date')->values()->toArray();
    }

    public function render()
    {
        return view('livewire.asset.report.asset-history-report', [
            'units' => $this->units,
            'assets' => $this->assets,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
