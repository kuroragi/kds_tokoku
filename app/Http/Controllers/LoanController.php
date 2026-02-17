<?php

namespace App\Http\Controllers;

use App\Models\EmployeeLoan;

class LoanController extends Controller
{
    public function loanIndex()
    {
        return view('pages.loan.employee-loan');
    }

    public function loanDetail(EmployeeLoan $loan)
    {
        return view('pages.loan.employee-loan-detail', compact('loan'));
    }
}
