<?php

use App\Http\Controllers\v1\Tenant\TenantController;
use App\Http\Controllers\v1\AuthController;
use Illuminate\Support\Facades\Route;


Route::prefix('central')->group(function () {

    // Create tenant
    Route::post('/tenants', [TenantController::class, 'store']);

    // Create owner inside tenant
   Route::post('/tenants/register', [TenantController::class, 'register']);
    Route::post('/tenants/{tenant}/login', [
       AuthController::class,
        'login'
    ]);
});
