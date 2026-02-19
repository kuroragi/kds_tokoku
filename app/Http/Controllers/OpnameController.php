<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OpnameController extends Controller
{
    public function stockOpnameIndex()
    {
        return view('pages.opname.stock-opname');
    }

    public function saldoOpnameIndex()
    {
        return view('pages.opname.saldo-opname');
    }
}
