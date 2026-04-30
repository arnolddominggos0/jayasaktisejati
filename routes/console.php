<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Log;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Schedule::command('shipments:send-eta-notifications')
//     ->dailyAt('09:00')
//     ->withoutOverlapping(30)
//     ->runInBackground()
//     ->onSuccess(function () {
//         \Log::info('Shipment ETA notifications completed successfully');
//     })
//     ->onFailure(function () {
//         \Log::error('Shipment ETA notifications failed');
//     });

Schedule::command('shipments:send-eta-notifications')
    ->everyMinute()
    ->withoutOverlapping(30)
    ->runInBackground()
    ->onSuccess(function () {
        Log::info('Shipment ETA notifications completed successfully' . now()->format('Y-m-d H:i:s'));
    })
    ->onFailure(function () {
        Log::error('Shipment ETA notifications failed' . now()->format('Y-m-d H:i:s'));
    });
