<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function purchaseOrderIndex()
    {
        return view('pages.purchase.purchase-order');
    }

    public function purchaseIndex()
    {
        return view('pages.purchase.purchase');
    }
}
