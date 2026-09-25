<?php

use App\Http\Controllers\v1\Central\CentralAuthController;
use App\Http\Controllers\v1\Central\CentralDashboardController;
use App\Http\Controllers\v1\Central\CentralSubscriptionPaymentController;
use App\Http\Controllers\v1\Tenant\SubscriptionController;
use App\Http\Controllers\v1\Tenant\SubscriptionPlanController;
use Illuminate\Support\Facades\Route;
Route::prefix('v1')->group(function () {
Route::middleware('auth:api')->group(function () {
    Route::apiResource('subscription-plans', SubscriptionPlanController::class);
    Route::apiResource('subscriptions', SubscriptionController::class);
});

Route::prefix('central')->group(function () {

    Route::post('/login', [CentralAuthController::class, 'login']);

    // Public callback routes for payment gateways (no auth required)
    Route::get('/subscription-payments/{payment}/verify-khalti', [CentralSubscriptionPaymentController::class, 'verifyKhalti'])->name('central.subscription-payments.verify-khalti');
    Route::get('/subscription-payments/{payment}/verify-esewa', [CentralSubscriptionPaymentController::class, 'verifyEsewa'])->name('central.subscription-payments.verify-esewa');

    Route::middleware('auth:api')->group(function () {
        Route::post('/tenants', [CentralAuthController::class, 'register']);
        Route::get('/me', [CentralAuthController::class, 'me']);
        Route::get('/profile', [CentralAuthController::class, 'profile']);
        Route::put('/profile', [CentralAuthController::class, 'update']);
        Route::post('/profile/change-password', [CentralAuthController::class, 'changePassword']);
        Route::post('/logout', [CentralAuthController::class, 'logout']);

        // Central Dashboard
        Route::get('/dashboard', [CentralDashboardController::class, 'index']);

        Route::get('/subscription-payments', [CentralSubscriptionPaymentController::class, 'index']);
        Route::post('/subscription-payments', [CentralSubscriptionPaymentController::class, 'store']);
        Route::post('/subscription-payments/initiate-from-plan', [CentralSubscriptionPaymentController::class, 'initiateFromPlan']);
        Route::get('/subscription-payments/{payment}', [CentralSubscriptionPaymentController::class, 'show']);
        Route::patch('/subscription-payments/{payment}/complete', [CentralSubscriptionPaymentController::class, 'complete']);
        Route::post('/subscription-payments/{payment}/complete-and-create-tenant', [CentralSubscriptionPaymentController::class, 'completeAndCreateTenant']);
        Route::patch('/subscription-payments/{payment}/fail', [CentralSubscriptionPaymentController::class, 'fail']);
    });

});
});