<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BranchController;

Route::prefix('user')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->name('user.register');
    Route::post('login',    [AuthController::class, 'login'])->middleware('throttle:10,1')->name('user.login');
    Route::get('users', [AuthController::class, 'index'])->name('users.index');


    // Protected (Sanctum token)
    Route::middleware(['auth:sanctum', 'scope.branch'])->group(function () {
        Route::get('branches', [BranchController::class, 'index'])->name('branches.index');
        Route::get('branches/{branch}', [BranchController::class, 'show'])->name('branches.show');
        Route::post('branches', [BranchController::class, 'store'])->name('branches.store')->middleware('role:super_admin');
        Route::patch('branches/{branch}', [BranchController::class, 'update'])->name('branches.update');
        Route::delete('branches/{branch}', [BranchController::class, 'destroy'])->name('branches.destroy')->middleware('role:super_admin');
        Route::post('logout', [AuthController::class, 'logout'])->name('user.logout');
    });
});
