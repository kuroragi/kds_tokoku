<?php

namespace App\Http\Controllers;

class SaldoController extends Controller
{
    public function providerIndex()
    {
        return view('pages.saldo.provider');
    }

    public function productIndex()
    {
        return view('pages.saldo.product');
    }

    public function topupIndex()
    {
        return view('pages.saldo.topup');
    }

    public function transactionIndex()
    {
        return view('pages.saldo.transaction');
    }
}
