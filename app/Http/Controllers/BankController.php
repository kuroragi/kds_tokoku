<?php

namespace App\Http\Controllers;

class BankController extends Controller
{
    public function bankIndex()
    {
        return view('pages.bank.index');
    }

    public function accountIndex()
    {
        return view('pages.bank.account');
    }

    public function transferIndex()
    {
        return view('pages.bank.transfer');
    }

    public function mutationIndex()
    {
        return view('pages.bank.mutation');
    }

    public function reconciliationIndex()
    {
        return view('pages.bank.reconciliation');
    }
}
