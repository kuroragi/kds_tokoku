<?php

namespace App\Providers;

use App\Models\BusinessUnit;
use App\Observers\BusinessUnitObserver;
use App\Services\SubscriptionService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Kuroragi\GeneralHelper\Macros\BlueprintMacros;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SubscriptionService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        BlueprintMacros::register();
        BusinessUnit::observe(BusinessUnitObserver::class);

        // ── Plan Feature Blade Directive ──
        // Usage: @planFeature('feature_key') ... @endPlanFeature
        Blade::if('planFeature', function (string $featureKey) {
            $user = auth()->user();
            if (!$user) return false;
            if ($user->hasRole('superadmin')) return true;

            return app(SubscriptionService::class)->hasFeature($user, $featureKey);
        });

        // ── Can Access Menu (role + plan combined) ──
        // Usage: @canAccessMenu('feature_key', ['pemilik', 'admin', 'kasir'])
        Blade::if('canAccessMenu', function (string $featureKey, array $roles = []) {
            $user = auth()->user();
            if (!$user) return false;
            if ($user->hasRole('superadmin')) return true;

            // Check role
            if (!empty($roles) && !$user->hasAnyRole($roles)) {
                return false;
            }

            // Check plan feature
            return app(SubscriptionService::class)->hasFeature($user, $featureKey);
        });
    }
}
