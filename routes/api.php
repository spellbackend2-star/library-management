<?php

use App\Http\Controllers\v1\Central\CentralAuthController;
use App\Http\Controllers\v1\Central\CentralSubscriptionPaymentController;
use App\Http\Controllers\v1\Tenant\SubscriptionController;
use App\Http\Controllers\v1\Tenant\SubscriptionPlanController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function () {
    Route::apiResource('subscription-plans', SubscriptionPlanController::class);
    Route::apiResource('subscriptions', SubscriptionController::class);
});

Route::prefix('central')->group(function () {

    Route::post('/login', [CentralAuthController::class, 'login']);

    Route::middleware('auth:api')->group(function () {
        Route::post('/tenants', [CentralAuthController::class, 'register']);
        Route::get('/me', [CentralAuthController::class, 'me']);
        Route::get('/profile', [CentralAuthController::class, 'profile']);
        Route::put('/profile', [CentralAuthController::class, 'update']);
        Route::post('/profile/change-password', [CentralAuthController::class, 'changePassword']);
        Route::post('/logout', [CentralAuthController::class, 'logout']);

        Route::post('/subscription-payments', [CentralSubscriptionPaymentController::class, 'store']);
        Route::get('/subscription-payments/{payment}', [CentralSubscriptionPaymentController::class, 'show']);
        Route::patch('/subscription-payments/{payment}/complete', [CentralSubscriptionPaymentController::class, 'complete']);
    });

});
