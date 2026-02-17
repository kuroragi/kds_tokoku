<?php

namespace App\Livewire\Payroll;

use App\Models\PayrollPeriod;
use App\Services\BusinessUnitService;
use Livewire\Component;

class PayrollReportRecap extends Component
{
    public $filterUnit = '';
    public $filterYear = '';
    public $filterMonth = '';

    public function getUnitsProperty()
    {
        return BusinessUnitService::getAvailableUnits();
    }

    public function getPeriodsProperty()
    {
        $query = PayrollPeriod::with('businessUnit')
            ->whereIn('status', ['calculated', 'approved', 'paid']);

        BusinessUnitService::applyBusinessUnitFilter($query, $this->filterUnit);

        if ($this->filterYear) {
            $query->where('year', $this->filterYear);
        }

        if ($this->filterMonth) {
            $query->where('month', $this->filterMonth);
        }

        return $query->orderByDesc('year')->orderByDesc('month')->get();
    }

    public function getSummaryProperty()
    {
        $periods = $this->periods;

        return [
            'total_earnings' => $periods->sum('total_earnings'),
            'total_benefits' => $periods->sum('total_benefits'),
            'total_deductions' => $periods->sum('total_deductions'),
            'total_tax' => $periods->sum('total_tax'),
            'total_net' => $periods->sum('total_net'),
            'period_count' => $periods->count(),
        ];
    }

    public function render()
    {
        return view('livewire.payroll.payroll-report-recap', [
            'periods' => $this->periods,
            'summary' => $this->summary,
            'units' => $this->units,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
            'years' => range(date('Y'), date('Y') - 2),
            'months' => [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
            ],
        ]);
    }
}
