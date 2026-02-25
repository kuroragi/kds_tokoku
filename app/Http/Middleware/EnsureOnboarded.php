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

        // Superadmin bypasses all onboarding checks
        if ($user->hasRole('superadmin')) {
            return $next($request);
        }

        // Step 1: Email must be verified
        if (!$user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        // Step 2: Must have an active subscription (own or business unit owner's)
        $subscriptionService = app(\App\Services\SubscriptionService::class);
        if (!$subscriptionService->hasActiveSubscription($user)) {
            // Check if has pending payment (own subscription only)
            $pending = $user->pendingSubscription;
            if ($pending) {
                return redirect()->route('onboarding.payment', ['subscription' => $pending->id]);
            }

            // Team members without subscription → show error
            if ($user->business_unit_id) {
                return redirect()->route('landing')
                    ->with('error', 'Langganan unit usaha Anda belum aktif. Hubungi pemilik unit usaha.');
            }

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
