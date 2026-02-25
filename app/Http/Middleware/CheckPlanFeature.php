<?php

namespace App\Http\Middleware;

use App\Services\SubscriptionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPlanFeature
{
    /**
     * Check if the user's plan has the required feature.
     * Usage: Route::middleware('plan:feature_key')
     *
     * Superadmin always passes.
     */
    public function handle(Request $request, Closure $next, string $featureKey): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(403, 'Unauthorized.');
        }

        // Superadmin bypasses all plan checks
        if ($user->hasRole('superadmin')) {
            return $next($request);
        }

        $subscriptionService = app(SubscriptionService::class);

        if (!$subscriptionService->hasFeature($user, $featureKey)) {
            abort(403, 'Fitur ini tidak tersedia dalam paket Anda. Silakan upgrade paket.');
        }

        return $next($request);
    }
}
