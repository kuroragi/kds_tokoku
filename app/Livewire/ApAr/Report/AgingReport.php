<?php

namespace App\Livewire\ApAr\Report;

use App\Services\ApArService;
use App\Services\BusinessUnitService;
use Livewire\Component;

class AgingReport extends Component
{
    public $filterUnit = '';
    public $reportType = 'payable'; // payable or receivable

    public function getUnitsProperty()
    {
        return BusinessUnitService::getAvailableUnits();
    }

    public function render()
    {
        $unitId = null;
        if (!BusinessUnitService::isSuperAdmin()) {
            $unitId = BusinessUnitService::getUserBusinessUnitId();
        } elseif ($this->filterUnit) {
            $unitId = (int) $this->filterUnit;
        }

        if ($this->reportType === 'payable') {
            $aging = ApArService::getPayableAging($unitId);
        } else {
            $aging = ApArService::getReceivableAging($unitId);
        }

        $grandTotal = collect($aging)->sum('total');

        return view('livewire.apar.report.aging-report', [
            'aging' => $aging,
            'grandTotal' => $grandTotal,
            'units' => $this->units,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
            'bucketLabels' => [
                'current' => 'Belum Jatuh Tempo',
                '1_30' => '1-30 Hari',
                '31_60' => '31-60 Hari',
                '61_90' => '61-90 Hari',
                'over_90' => '> 90 Hari',
            ],
        ]);
    }
}
