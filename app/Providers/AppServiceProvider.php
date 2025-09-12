<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Shipment;
use App\Observers\ShipmentObserver;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;

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
        Carbon::setLocale('id');
        Date::setLocale('id');

        $tr = Carbon::getTranslator();
        if (method_exists($tr, 'setTranslations')) {
            $tr->setTranslations([
                'ago'      => ':time lalu',
                'from_now' => 'dalam :time',
            ]);
            Shipment::observe(ShipmentObserver::class);
        }
    }
}
