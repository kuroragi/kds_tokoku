<?php

namespace App\Providers;

use App\Models\BusinessUnit;
use App\Observers\BusinessUnitObserver;
use Illuminate\Support\ServiceProvider;
use Kuroragi\GeneralHelper\Macros\BlueprintMacros;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        BlueprintMacros::register();
        BusinessUnit::observe(BusinessUnitObserver::class);
    }
}
