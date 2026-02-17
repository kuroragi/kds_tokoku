<?php

namespace App\Livewire\Asset\Report;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Services\AssetService;
use App\Services\BusinessUnitService;
use Livewire\Component;

class BookValueReport extends Component
{
    public $filterUnit = '';
    public $filterCategory = '';
    public $search = '';
    public $sortField = 'code';
    public $sortDirection = 'asc';

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
        if ($this->filterUnit) {
            $query->byBusinessUnit($this->filterUnit);
        } elseif (!BusinessUnitService::isSuperAdmin()) {
            $unitId = BusinessUnitService::getUserBusinessUnitId();
            if ($unitId) $query->byBusinessUnit($unitId);
        }
        return $query->orderBy('name')->get();
    }

    public function render()
    {
        $query = Asset::with(['businessUnit', 'assetCategory', 'depreciations']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('code', 'like', "%{$this->search}%")
                    ->orWhere('name', 'like', "%{$this->search}%");
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

        // Hanya tampilkan aset aktif
        $query->where('status', 'active');

        $assets = $query->orderBy($this->sortField, $this->sortDirection)->get();

        $service = app(AssetService::class);
        $reportData = $assets->map(function ($asset) use ($service) {
            $accumulated = $service->getAccumulatedDepreciation($asset);
            $bookValue = $asset->acquisition_cost - $accumulated;
            $depreciationPercent = $asset->acquisition_cost > 0
                ? round(($accumulated / $asset->acquisition_cost) * 100, 1) : 0;

            return [
                'asset' => $asset,
                'acquisition_cost' => $asset->acquisition_cost,
                'accumulated_depreciation' => $accumulated,
                'book_value' => $bookValue,
                'salvage_value' => $asset->salvage_value,
                'depreciation_percent' => $depreciationPercent,
            ];
        });

        $summary = [
            'total_acquisition' => $reportData->sum('acquisition_cost'),
            'total_accumulated' => $reportData->sum('accumulated_depreciation'),
            'total_book_value' => $reportData->sum('book_value'),
            'total_assets' => $reportData->count(),
        ];

        return view('livewire.asset.report.book-value-report', [
            'reportData' => $reportData,
            'units' => $this->units,
            'categories' => $this->categories,
            'summary' => $summary,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
