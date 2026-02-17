<?php

namespace App\Http\Controllers;

class ApArController extends Controller
{
    // Transactions
    public function payableIndex()
    {
        return view('pages.apar.payable');
    }

    public function receivableIndex()
    {
        return view('pages.apar.receivable');
    }

    // Reports
    public function reportAging()
    {
        return view('pages.apar.report-aging');
    }

    public function reportOutstanding()
    {
        return view('pages.apar.report-outstanding');
    }

    public function reportPaymentHistory()
    {
        return view('pages.apar.report-payment-history');
    }
}
