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
}
