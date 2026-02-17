<?php

namespace App\Livewire\Asset\Report;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Services\BusinessUnitService;
use Livewire\Component;

class AssetRegisterReport extends Component
{
    public $filterUnit = '';
    public $filterCategory = '';
    public $filterStatus = '';
    public $filterCondition = '';
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

        if ($this->filterCondition) {
            $query->where('condition', $this->filterCondition);
        }

        $assets = $query->orderBy($this->sortField, $this->sortDirection)->get();

        // Summary
        $summary = [
            'total_assets' => $assets->count(),
            'total_acquisition' => $assets->sum('acquisition_cost'),
            'total_active' => $assets->where('status', 'active')->count(),
            'total_disposed' => $assets->where('status', 'disposed')->count(),
        ];

        return view('livewire.asset.report.asset-register-report', [
            'assets' => $assets,
            'units' => $this->units,
            'categories' => $this->categories,
            'statuses' => Asset::STATUSES,
            'conditions' => Asset::CONDITIONS,
            'summary' => $summary,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
