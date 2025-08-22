<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BranchController;


Route::prefix('user')->group(function () {
    // Public
    Route::post('register', [AuthController::class, 'register'])->name('user.register');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('user.login');


    // Protected (Bearer token + branch scoping)
    Route::middleware(['auth:sanctum', 'scope.branch'])->group(function () {
        Route::get('me', [AuthController::class, 'me'])->name('user.me');
        Route::post('logout', [AuthController::class, 'logout'])->name('user.logout');


        // INTERNAL ONLY
        Route::middleware('role:super_admin|office_admin|field_coordinator')->group(function () {
            Route::get('users', [AuthController::class, 'index'])->name('users.index');


            Route::get('branches', [BranchController::class, 'index'])->name('branches.index');
            Route::get('branches/{branch}', [BranchController::class, 'show'])->name('branches.show')->whereNumber('branch');
            Route::patch('branches/{branch}', [BranchController::class, 'update'])->name('branches.update')->whereNumber('branch');
        });


        // SUPER ADMIN ONLY
        Route::post('branches', [BranchController::class, 'store'])->middleware('role:super_admin')->name('branches.store');
        Route::delete('branches/{branch}', [BranchController::class, 'destroy'])->middleware('role:super_admin')->name('branches.destroy');
    });
});
