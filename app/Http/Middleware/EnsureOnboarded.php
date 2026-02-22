<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboarded
{
    /**
     * Ensure authenticated user has completed the full onboarding:
     * 1. Email verified
     * 2. Active subscription
     * 3. Business unit assigned
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Step 1: Email must be verified
        if (!$user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        // Step 2: Must have an active subscription
        if (!$user->activeSubscription) {
            return redirect()->route('landing')
                ->with('info', 'Silakan pilih paket terlebih dahulu.');
        }

        // Step 3: Must have a business unit
        if (!$user->business_unit_id) {
            return redirect()->route('onboarding.setup-instance');
        }

        return $next($request);
    }
}
