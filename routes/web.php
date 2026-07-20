<?php

use App\Http\Controllers\Jsl\AboutController;
use App\Http\Controllers\Jsl\ContactController;
use App\Http\Controllers\Jsl\GalleryController;
use App\Http\Controllers\Jsl\HomeController;
use App\Http\Controllers\Jsl\ServicesController;
use App\Http\Controllers\Jsl\VesselListingController;
use App\Http\Controllers\Public\LandingController;
use App\Http\Controllers\Public\TrackingController;
use App\Http\Controllers\ShipmentPrintController;
use App\Http\Controllers\VoyageQuickReportController;
use Illuminate\Support\Facades\Route;

Route::get('/ping', fn() => 'pong');

Route::get('/', function () {
    return view('landing');
});

Route::get('/', [LandingController::class, 'index'])->name('landing');

Route::get('/tracking', [TrackingController::class, 'index'])->name('tracking');
Route::post('/tracking/search', [TrackingController::class, 'search'])->name('tracking.search');

Route::middleware(['auth'])->group(function () {
    Route::get('/shipments/{shipment}/waybill', [ShipmentPrintController::class, 'waybill'])
        ->name('shipments.print.waybill');

    Route::get('/shipments/{shipment}/resi', [ShipmentPrintController::class, 'resi'])
        ->name('shipments.resi');

    Route::get('/shipments/{shipment}/packing-list', [ShipmentPrintController::class, 'packingList'])
        ->name('shipments.print.packing');

    Route::get('/voyages/{voyageId}/quick-report', [VoyageQuickReportController::class, 'generate'])
        ->name('voyage.quick-report');
});

Route::get('/tracking/{code}', function (string $code) {
    return "Tracking page for {$code}";
})->name('tracking.show');

// ── JSL Website (Public) ──────────────────────────────────────────────────────
Route::prefix('jsl')->name('jsl.')->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/about', [AboutController::class, 'index'])->name('about');
    Route::get('/services', [ServicesController::class, 'index'])->name('services');
    Route::get('/trading', [VesselListingController::class, 'index'])->name('trading.index');
    Route::get('/trading/{refCode}', [VesselListingController::class, 'show'])->name('trading.show');
    Route::get('/gallery', [GalleryController::class, 'index'])->name('gallery');
    Route::get('/contact', [ContactController::class, 'index'])->name('contact');
    Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');
    Route::get('/contact/success', [ContactController::class, 'success'])->name('contact.success');
});

// Local-only debug login route.
if (app()->environment('local')) {
    Route::get('/_uxlist02-login', function () {
        auth()->loginUsingId(5);
        return redirect('/admin/shipments');
    });
}
