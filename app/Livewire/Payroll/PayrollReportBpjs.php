<?php

namespace App\Livewire\Payroll;

use App\Models\PayrollEntryDetail;
use App\Services\BusinessUnitService;
use Livewire\Component;

class PayrollReportBpjs extends Component
{
    public $filterUnit = '';
    public $filterYear = '';
    public $filterMonth = '';

    public function getUnitsProperty()
    {
        return BusinessUnitService::getAvailableUnits();
    }

    public function getDetailsProperty()
    {
        $query = PayrollEntryDetail::with(['payrollEntry.employee', 'payrollEntry.payrollPeriod'])
            ->where('category', 'bpjs')
            ->whereHas('payrollEntry.payrollPeriod', function ($q) {
                $q->whereIn('status', ['calculated', 'approved', 'paid']);

                BusinessUnitService::applyBusinessUnitFilter($q, $this->filterUnit);

                if ($this->filterYear) {
                    $q->where('year', $this->filterYear);
                }
                if ($this->filterMonth) {
                    $q->where('month', $this->filterMonth);
                }
            });

        return $query->orderBy('payroll_entry_id')->get();
    }

    /**
     * Group BPJS details by employee for table display.
     */
    public function getGroupedProperty()
    {
        $details = $this->details;

        return $details->groupBy(function ($detail) {
            return $detail->payrollEntry->employee_id;
        })->map(function ($employeeDetails) {
            $employee = $employeeDetails->first()->payrollEntry->employee;
            $period = $employeeDetails->first()->payrollEntry->payrollPeriod;

            $components = [];
            foreach ($employeeDetails as $detail) {
                $components[$detail->component_name] = $detail->amount;
            }

            $companyTotal = $employeeDetails->where('type', 'benefit')->sum('amount');
            $employeeTotal = $employeeDetails->where('type', 'deduction')->sum('amount');

            return [
                'employee' => $employee,
                'period' => $period,
                'base_salary' => $employeeDetails->first()->payrollEntry->base_salary,
                'components' => $components,
                'company_total' => $companyTotal,
                'employee_total' => $employeeTotal,
                'grand_total' => $companyTotal + $employeeTotal,
            ];
        });
    }

    public function getSummaryProperty()
    {
        $grouped = $this->grouped;

        return [
            'company_total' => $grouped->sum('company_total'),
            'employee_total' => $grouped->sum('employee_total'),
            'grand_total' => $grouped->sum('grand_total'),
        ];
    }

    public function render()
    {
        return view('livewire.payroll.payroll-report-bpjs', [
            'grouped' => $this->grouped,
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
