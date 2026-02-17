<?php

namespace App\Livewire\Asset\Report;

use App\Models\AssetDepreciation;
use App\Models\Period;
use App\Services\BusinessUnitService;
use Livewire\Component;

class DepreciationReport extends Component
{
    public $filterUnit = '';
    public $filterPeriod = '';
    public $search = '';
    public $sortField = 'depreciation_date';
    public $sortDirection = 'desc';

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

    public function getPeriodsProperty()
    {
        return Period::orderBy('year', 'desc')->orderBy('month', 'desc')->get();
    }

    public function render()
    {
        $query = AssetDepreciation::with(['asset.businessUnit', 'asset.assetCategory', 'period', 'journalMaster']);

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

        if ($this->filterPeriod) {
            $query->where('period_id', $this->filterPeriod);
        }

        $depreciations = $query->orderBy($this->sortField, $this->sortDirection)->get();

        $summary = [
            'total_depreciation' => $depreciations->sum('depreciation_amount'),
            'total_records' => $depreciations->count(),
            'unique_assets' => $depreciations->pluck('asset_id')->unique()->count(),
        ];

        return view('livewire.asset.report.depreciation-report', [
            'depreciations' => $depreciations,
            'units' => $this->units,
            'periods' => $this->periods,
            'summary' => $summary,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
