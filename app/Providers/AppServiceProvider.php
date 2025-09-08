<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Shipment;
use App\Observers\ShipmentObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void {}

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Shipment::observe(ShipmentObserver::class);
    }
}
