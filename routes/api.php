<?php

use App\Http\Controllers\Central\CentralAuthController;
use App\Http\Controllers\v1\Tenant\TenantController;
use Illuminate\Support\Facades\Route;


Route::prefix('central')->group(function () {

    Route::post('/login', [CentralAuthController::class, 'login']);

    Route::middleware('auth:api')->group(function () {
        Route::post('/tenants', [TenantController::class, 'register']);
        Route::get('/me', [CentralAuthController::class, 'me']);
    });

});