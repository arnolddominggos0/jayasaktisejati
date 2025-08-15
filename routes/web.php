<?php

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::get('/ping', fn () => 'pong'); 

Route::post('/login',  [AuthController::class, 'login'])->middleware(['web','guest','throttle:10,1']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware(['web','auth']);

