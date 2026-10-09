<?php

use App\Http\Controllers\v1\Central\CentralAuthController;
use App\Http\Controllers\v1\Central\CentralCouponController;
use App\Http\Controllers\v1\Central\CentralDashboardController;
use App\Http\Controllers\v1\Central\CentralInvoiceController;
use App\Http\Controllers\v1\Central\CentralPasswordController;
use App\Http\Controllers\v1\Central\CentralProfileController;
use App\Http\Controllers\v1\Central\CentralRegistrationController;
use App\Http\Controllers\v1\Central\CentralSettingController;
use App\Http\Controllers\v1\Central\CentralStaffController;
use App\Http\Controllers\v1\Central\CentralSubscriptionPaymentController;
use App\Http\Controllers\v1\Central\CentralTenantController;
use App\Http\Controllers\v1\Tenant\SubscriptionController;
use App\Http\Controllers\v1\Tenant\SubscriptionPlanController;
use App\Services\CentralSettingService;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::middleware('auth:api')->group(function () {
        Route::get('subscription-plans/all', [SubscriptionPlanController::class, 'all']);
        Route::apiResource('subscription-plans', SubscriptionPlanController::class);
        Route::apiResource('subscriptions', SubscriptionController::class);
        Route::patch('/subscriptions/{subscription}/status', [SubscriptionController::class, 'updateStatus']);
        Route::patch('/subscriptions/{subscription}/cancel', [SubscriptionController::class, 'cancel']);
        Route::post('/subscriptions/{subscription}/change-plan/preview', [SubscriptionController::class, 'previewChangePlan']);
        Route::patch('/subscriptions/{subscription}/change-plan', [SubscriptionController::class, 'changePlan']);
    });

    Route::prefix('central')->group(function () {

        Route::post('/login', [CentralAuthController::class, 'login']);

        // Public callback routes for payment gateways (no auth required)
        Route::get('/subscription-payments/{payment}/verify-khalti', [CentralSubscriptionPaymentController::class, 'verifyKhalti'])->name('central.subscription-payments.verify-khalti');
        Route::get('/subscription-payments/{payment}/verify-esewa', [CentralSubscriptionPaymentController::class, 'verifyEsewa'])->name('central.subscription-payments.verify-esewa');

        // Public "how to pay" status polling after the gateway redirect
        Route::get('/subscription-payments/{payment}/status', [CentralSubscriptionPaymentController::class, 'paymentStatus'])->name('central.subscription-payments.status');

        // Public plan checkout and post-payment tenant registration.
        Route::post('/subscription-payments/initiate-from-plan', [CentralSubscriptionPaymentController::class, 'initiateFromPlan']);
        Route::post('/subscription-payments/pay', [CentralSubscriptionPaymentController::class, 'payInvoice'])
            ->name('central.subscription-payments.pay-invoice');
        Route::post('/subscription-payments/{payment}/complete-and-create-tenant', [CentralSubscriptionPaymentController::class, 'completeAndCreateTenant'])
            ->name('central.subscription-payments.complete-and-create-tenant');

        Route::middleware('auth:api')->group(function () {
            Route::post('/tenants', [CentralRegistrationController::class, 'register']);
            Route::get('/me', [CentralProfileController::class, 'me']);
            Route::get('/profile', [CentralProfileController::class, 'profile']);
            Route::put('/profile', [CentralProfileController::class, 'update']);
            Route::post('/profile/change-password', [CentralPasswordController::class, 'changePassword']);
            Route::post('/logout', [CentralAuthController::class, 'logout']);

            Route::patch('/staff/{staff}/role', [CentralStaffController::class, 'assignRole'])
                ->name('central.staff.assign-role');
            Route::apiResource('/staff', CentralStaffController::class)
                ->names('central.staff');

            // Central Dashboard
            Route::get('/dashboard', [CentralDashboardController::class, 'index']);

            // Central Settings
            Route::get('/settings', [CentralSettingController::class, 'index']);
            Route::get('/settings/{group}', [CentralSettingController::class, 'show'])
                ->whereIn('group', CentralSettingService::groups());
            Route::put('/settings/{group}', [CentralSettingController::class, 'update'])
                ->whereIn('group', CentralSettingService::groups());

            // Central Invoices
            Route::get('/invoices', [CentralInvoiceController::class, 'index']);
            Route::get('/invoices/number/{invoiceNumber}', [CentralInvoiceController::class, 'showByNumber']);
            Route::get('/invoices/{id}', [CentralInvoiceController::class, 'show']);

            Route::get('/subscription-payments', [CentralSubscriptionPaymentController::class, 'index']);
            Route::post('/subscription-payments', [CentralSubscriptionPaymentController::class, 'store']);
            Route::get('/subscription-payments/{payment}', [CentralSubscriptionPaymentController::class, 'show']);
            Route::post('/subscription-payments/{payment}/complete', [CentralSubscriptionPaymentController::class, 'complete']);
            Route::patch('/subscription-payments/{payment}/fail', [CentralSubscriptionPaymentController::class, 'fail']);
            Route::post('/subscription-payments/{payment}/pay', [CentralSubscriptionPaymentController::class, 'pay'])->name('central.subscription-payments.pay');

            // Central Tenants
            Route::get('/tenants', [CentralTenantController::class, 'index']);
            Route::get('/tenants/{tenant}', [CentralTenantController::class, 'show']);
            Route::put('/tenants/{tenant}', [CentralTenantController::class, 'update']);
            Route::patch('/tenants/{tenant}/status', [CentralTenantController::class, 'updateStatus']);
            Route::patch('/tenants/{tenant}/review', [CentralTenantController::class, 'review']);

            // Central Coupons
            Route::apiResource('/coupons', CentralCouponController::class)
                ->names('central.coupons');
        });

    });
});
