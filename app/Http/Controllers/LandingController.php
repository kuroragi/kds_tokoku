<?php

namespace App\Http\Controllers;

use App\Models\Plan;

class LandingController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Fully onboarded users → dashboard
        if ($user && $user->hasVerifiedEmail() && $user->activeSubscription && $user->business_unit_id) {
            return redirect()->route('dashboard');
        }

        // Verified user with subscription but no instance → setup instance
        if ($user && $user->hasVerifiedEmail() && $user->activeSubscription && !$user->business_unit_id) {
            return redirect()->route('onboarding.setup-instance');
        }

        $plans = Plan::active()
            ->ordered()
            ->with('features')
            ->get();

        return view('landing', compact('plans'));
    }
}
