<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SalesController extends Controller
{
    public function salesIndex()
    {
        return view('pages.sales.sales');
    }
}
