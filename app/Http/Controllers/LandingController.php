<?php

namespace App\Http\Controllers;

use App\Models\Plan;

class LandingController extends Controller
{
    public function index()
    {
        // Authenticated users go straight to dashboard
        if (auth()->check()) {
            return redirect()->route('dashboard');
        }

        $plans = Plan::active()
            ->ordered()
            ->with('features')
            ->get();

        return view('landing', compact('plans'));
    }
}
