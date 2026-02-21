<?php

namespace App\Http\Controllers;

use App\Models\Plan;

class LandingController extends Controller
{
    public function index()
    {
        $plans = Plan::active()
            ->ordered()
            ->with('features')
            ->get();

        return view('landing', compact('plans'));
    }
}
