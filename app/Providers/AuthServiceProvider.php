<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends \Illuminate\Foundation\Support\Providers\AuthServiceProvider
{
    protected $policies = [
        \App\Models\Shipment::class      => \App\Policies\ShipmentPolicy::class,
        \App\Models\ShipmentTrack::class => \App\Policies\ShipmentTrackPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
