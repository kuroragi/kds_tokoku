<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AccountingController extends Controller
{
    public function coa(){
        return view('pages.accounting.coa');
    }

    public function journal(){
        return view('pages.accounting.journal');
    }

    public function generalLedger(){
        return view('pages.accounting.general-ledger');
    }

    public function generalLedgerDetail(\App\Models\COA $coa){
        return view('pages.accounting.general-ledger-detail', compact('coa'));
    }

    public function adjustmentJournal(){
        return view('pages.accounting.adjustment-journal');
    }

    public function trialBalance(){
        return view('pages.accounting.trial-balance');
    }

    public function incomeStatement(){
        return view('pages.accounting.income-statement');
    }

    public function adjustedTrialBalance(){
        return view('pages.accounting.adjusted-trial-balance');
    }

    public function taxClosing(){
        return view('pages.accounting.tax-closing');
    }

    public function finalBalanceSheet(){
        return view('pages.accounting.final-balance-sheet');
    }

    public function taxReport(){
        return view('pages.tax.report');
    }
}
