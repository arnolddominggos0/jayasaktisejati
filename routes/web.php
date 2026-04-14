<?php

use App\Http\Controllers\Public\LandingController;
use App\Http\Controllers\Public\TrackingController;
use App\Http\Controllers\ShipmentPrintController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes (No Login Required)
|--------------------------------------------------------------------------
*/

// Landing Page
Route::get('/', [LandingController::class, 'index'])->name('landing');

// Public Tracking
Route::get('/tracking', [TrackingController::class, 'index'])->name('tracking');
Route::post('/tracking/search', [TrackingController::class, 'search'])->name('tracking.search');

Route::middleware(['auth'])->group(function () {
    Route::get('/shipments/{shipment}/waybill', [ShipmentPrintController::class, 'waybill'])
        ->name('shipments.print.waybill');

    Route::get('/shipments/{shipment}/resi', [ShipmentPrintController::class, 'resi'])
        ->name('shipments.resi');

    Route::get('/shipments/{shipment}/packing-list', [ShipmentPrintController::class, 'packingList'])
        ->name('shipments.print.packing');
});

Route::get('/tracking/{code}', function (string $code) {
    return "Tracking page for {$code}";
})->name('tracking.show');

Route::get('/_debug/fc-login-mw', function () {
    $route = Route::getRoutes()->getByName('filament.fc.auth.login');

    return response()->json([
        'uri' => $route?->uri(),
        'action' => $route?->getActionName(),
        'middleware' => $route?->gatherMiddleware(),
    ]);
});
