<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Api\ShipmentController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\VoyageController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\AppSheetWebhookController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// AppSheet Webhook Routes (Public - validated by signature)
Route::prefix('appsheet')->group(function () {
    Route::post('webhook', [AppSheetWebhookController::class, 'handle'])->name('appsheet.webhook');
    Route::match(['get', 'post'], 'briefing-summary', [AppSheetWebhookController::class, 'briefingSummary'])->name('appsheet.briefing-summary');
    Route::get('test', [AppSheetWebhookController::class, 'test'])->name('appsheet.test');
});

// Public routesap
Route::prefix('user')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->name('user.register');
    Route::post('login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1')
        ->name('user.login');
});

// Protected routes
Route::middleware(['auth:sanctum', 'scope.branch'])->group(function () {
    
    // User profile
    Route::prefix('user')->group(function () {
        Route::get('me', [AuthController::class, 'me'])->name('user.me');
        Route::post('logout', [AuthController::class, 'logout'])->name('user.logout');
    });

    // Dashboard & Statistics
    Route::prefix('dashboard')->group(function () {
        Route::get('stats', [DashboardController::class, 'stats'])->name('dashboard.stats');
        Route::get('shipment-stats', [DashboardController::class, 'shipmentStats'])->name('dashboard.shipment-stats');
        Route::get('recent-shipments', [DashboardController::class, 'recentShipments'])->name('dashboard.recent-shipments');
        Route::get('upcoming-voyages', [DashboardController::class, 'upcomingVoyages'])->name('dashboard.upcoming-voyages');
    });

    // INTERNAL ONLY - Office Admin, Field Coordinator, Super Admin
    Route::middleware('role:super_admin|office_admin|field_coordinator')->group(function () {
        
        // Users
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        
        // Branches
        Route::get('branches', [BranchController::class, 'index'])->name('branches.index');
        Route::get('branches/{branch}', [BranchController::class, 'show'])
            ->whereNumber('branch')
            ->name('branches.show');
        Route::patch('branches/{branch}', [BranchController::class, 'update'])
            ->whereNumber('branch')
            ->name('branches.update');
        
        // Shipments
        Route::prefix('shipments')->group(function () {
            Route::get('/', [ShipmentController::class, 'index'])->name('shipments.index');
            Route::post('/', [ShipmentController::class, 'store'])->name('shipments.store');
            Route::get('{shipment}', [ShipmentController::class, 'show'])
                ->whereNumber('shipment')
                ->name('shipments.show');
            Route::put('{shipment}', [ShipmentController::class, 'update'])
                ->whereNumber('shipment')
                ->name('shipments.update');
            Route::delete('{shipment}', [ShipmentController::class, 'destroy'])
                ->whereNumber('shipment')
                ->name('shipments.destroy');
            Route::get('{shipment}/tracking', [ShipmentController::class, 'tracking'])
                ->whereNumber('shipment')
                ->name('shipments.tracking');
        });
        
        // Customers
        Route::prefix('customers')->group(function () {
            Route::get('/', [CustomerController::class, 'index'])->name('customers.index');
            Route::get('{customer}', [CustomerController::class, 'show'])
                ->whereNumber('customer')
                ->name('customers.show');
        });
        
        // Voyages
        Route::prefix('voyages')->group(function () {
            Route::get('/', [VoyageController::class, 'index'])->name('voyages.index');
            Route::get('{voyage}', [VoyageController::class, 'show'])
                ->whereNumber('voyage')
                ->name('voyages.show');
        });
    });

    // SUPER ADMIN ONLY
    Route::middleware('role:super_admin')->group(function () {
        // Branches
        Route::post('branches', [BranchController::class, 'store'])->name('branches.store');
        Route::delete('branches/{branch}', [BranchController::class, 'destroy'])
            ->whereNumber('branch')
            ->name('branches.destroy');
    });
});
