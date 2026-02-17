<?php

namespace App\Livewire\Payroll;

use App\Models\PayrollEntry;
use App\Models\PayrollPeriod;
use App\Services\BusinessUnitService;
use Livewire\Component;

class PayrollReportEmployee extends Component
{
    public $filterUnit = '';
    public $filterYear = '';
    public $filterEmployee = '';

    public function getUnitsProperty()
    {
        return BusinessUnitService::getAvailableUnits();
    }

    public function getEntriesProperty()
    {
        $query = PayrollEntry::with(['employee.position', 'payrollPeriod.businessUnit'])
            ->whereHas('payrollPeriod', function ($q) {
                $q->whereIn('status', ['calculated', 'approved', 'paid']);

                BusinessUnitService::applyBusinessUnitFilter($q, $this->filterUnit);

                if ($this->filterYear) {
                    $q->where('year', $this->filterYear);
                }
            });

        if ($this->filterEmployee) {
            $query->whereHas('employee', function ($q) {
                $q->where('name', 'like', "%{$this->filterEmployee}%")
                    ->orWhere('code', 'like', "%{$this->filterEmployee}%");
            });
        }

        return $query->orderBy('payroll_period_id', 'desc')->get();
    }

    public function getSummaryProperty()
    {
        $entries = $this->entries;

        return [
            'total_earnings' => $entries->sum('total_earnings'),
            'total_deductions' => $entries->sum('total_deductions'),
            'total_net' => $entries->sum('net_salary'),
            'employee_count' => $entries->groupBy('employee_id')->count(),
        ];
    }

    public function render()
    {
        return view('livewire.payroll.payroll-report-employee', [
            'entries' => $this->entries,
            'summary' => $this->summary,
            'units' => $this->units,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
            'years' => range(date('Y'), date('Y') - 2),
        ]);
    }
}
