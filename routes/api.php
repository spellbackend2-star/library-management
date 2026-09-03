<?php

use App\Http\Controllers\Central\CentralAuthController;
use Illuminate\Support\Facades\Route;


Route::prefix('central')->group(function () {

    Route::post('/login', [CentralAuthController::class, 'login']);

    Route::middleware('auth:api')->group(function () {
        Route::post('/tenants', [CentralAuthController::class, 'register']);
        Route::get('/me', [CentralAuthController::class, 'me']);
    });

});
