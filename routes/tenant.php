<?php

declare(strict_types=1);

use App\Http\Controllers\v1\AuthController;
use App\Http\Controllers\v1\Tenant\AuthorController;
use App\Http\Controllers\v1\Tenant\BookAuthorController;
use App\Http\Controllers\v1\Tenant\BookCategoryController;
use App\Http\Controllers\v1\Tenant\BookController;
use App\Http\Controllers\v1\Tenant\BookEditionController;
use App\Http\Controllers\v1\Tenant\CategoryController;
use App\Http\Controllers\v1\Tenant\CopyController;
use App\Http\Controllers\v1\Tenant\MemberController;
use App\Http\Controllers\v1\Tenant\MembershipTypeController;
use App\Http\Controllers\v1\Tenant\PublisherController;
use App\Http\Controllers\v1\Tenant\StaffController;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
*/

Route::middleware([
    'api',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {

    // Tenant login
    Route::post('/login', [
        AuthController::class,
        'login'
    ]);

    // PROTECTED
    Route::middleware('auth:api')->group(function () {

        Route::apiResource(
            'membership-types',
            MembershipTypeController::class
        );

        Route::apiResource(
            'members',
            MemberController::class
        );
        // Setup tenant owner
        Route::patch(
            'staff/setup-owner',
            [StaffController::class, 'setupOwner']
        );

        // Staff CRUD
        Route::apiResource('staff', StaffController::class)
            ->middlewareFor('index', 'can:staff.view')
            ->middlewareFor('show', 'can:staff.view')
            ->middlewareFor('store', 'can:staff.create')
            ->middlewareFor('update', 'can:staff.update')
            ->middlewareFor('destroy', 'can:staff.delete');

        // Activate
        Route::patch(
            'staff/{staff}/activate',
            [StaffController::class, 'activate']
        )->middleware('can:staff.update');

        // Deactivate
        Route::patch(
            'staff/{staff}/deactivate',
            [StaffController::class, 'deactivate']
        )->middleware('can:staff.update');

        // Assign role
        Route::patch(
            'staff/{staff}/role',
            [StaffController::class, 'assignRole']
        )->middleware('can:staff.assign-role');

        Route::apiResource(
            'publishers',
            PublisherController::class
        );

        Route::apiResource(
            'authors',
            AuthorController::class
        );

        Route::apiResource(
            'categories',
            CategoryController::class
        );

        Route::apiResource(
            'books',
            BookController::class
        );

        Route::apiResource(
            'book-editions',
            BookEditionController::class
        );

        Route::apiResource(
            'book-authors',
            BookAuthorController::class
        );

        Route::apiResource(
            'book-categories',
            BookCategoryController::class
        );

        Route::apiResource(
            'copies',
            CopyController::class
        );
    });
});
