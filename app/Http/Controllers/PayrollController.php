<?php

namespace App\Http\Controllers;

use App\Models\PayrollPeriod;

class PayrollController extends Controller
{
    public function salaryComponentIndex()
    {
        return view('pages.payroll.salary-component');
    }

    public function payrollSettingIndex()
    {
        return view('pages.payroll.payroll-setting');
    }

    public function payrollIndex()
    {
        return view('pages.payroll.payroll-index');
    }

    public function payrollDetail(PayrollPeriod $payrollPeriod)
    {
        return view('pages.payroll.payroll-detail', compact('payrollPeriod'));
    }

    // Payroll Reports
    public function reportRecap()
    {
        return view('pages.payroll.report-recap');
    }

    public function reportEmployee()
    {
        return view('pages.payroll.report-employee');
    }

    public function reportBpjs()
    {
        return view('pages.payroll.report-bpjs');
    }
}
