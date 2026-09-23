<?php

declare(strict_types=1);

use App\Http\Controllers\v1\AuthController;
use App\Http\Controllers\v1\Tenant\AuthorController;
use App\Http\Controllers\v1\Tenant\BookAuthorController;
use App\Http\Controllers\v1\Tenant\BookCategoryController;
use App\Http\Controllers\v1\Tenant\BookController;
use App\Http\Controllers\v1\Tenant\BookEditionController;
use App\Http\Controllers\v1\Tenant\BookingController;
use App\Http\Controllers\v1\Tenant\BookingDetailsController;
use App\Http\Controllers\v1\Tenant\BorrowController;
use App\Http\Controllers\v1\Tenant\CategoryController;
use App\Http\Controllers\v1\Tenant\CopyController;
use App\Http\Controllers\v1\Tenant\CouponController;
use App\Http\Controllers\v1\Tenant\FineController;
use App\Http\Controllers\v1\Tenant\FloorController;
use App\Http\Controllers\v1\Tenant\InvoiceController;
use App\Http\Controllers\v1\Tenant\LockerAssigmentsController;
use App\Http\Controllers\v1\Tenant\LockerController;
use App\Http\Controllers\v1\Tenant\MemberController;
use App\Http\Controllers\v1\Tenant\PackageController;
use App\Http\Controllers\v1\Tenant\PaymentController;
use App\Http\Controllers\v1\Tenant\PublisherController;
use App\Http\Controllers\v1\Tenant\RoomController;
use App\Http\Controllers\v1\Tenant\SeatCategoryController;
use App\Http\Controllers\v1\Tenant\SeatController;
use App\Http\Controllers\v1\Tenant\SettingController;
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

    /*
    |--------------------------------------------------------------------------
    | Public Routes
    |--------------------------------------------------------------------------
    */

    // Tenant login
    Route::post('/login', [AuthController::class, 'login']);

    // Khalti callback / verification
    Route::get(
        '/payments/khalti/verify/{paymentId}',
        [PaymentController::class, 'verifyKhalti']
    )->name('payments.khalti.verify');

    // eSewa callback / verification
    Route::get(
        '/payments/esewa/verify/{paymentId}',
        [PaymentController::class, 'verifyEsewa']
    )->name('payments.esewa.verify');

    /*
    |--------------------------------------------------------------------------
    | Protected Routes
    |--------------------------------------------------------------------------
    */

    Route::middleware('auth:api')->group(function () {

        // Profile
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::put('/profile', [AuthController::class, 'update']);
        Route::post('/profile/change-password', [AuthController::class, 'changePassword']);
        Route::post('/logout', [AuthController::class, 'logout']);

        // Members
        Route::apiResource('members', MemberController::class);

        // Staff owner setup
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

        // Activate staff
        Route::patch(
            'staff/{staff}/activate',
            [StaffController::class, 'activate']
        )->middleware('can:staff.update');

        // Deactivate staff
        Route::patch(
            'staff/{staff}/deactivate',
            [StaffController::class, 'deactivate']
        )->middleware('can:staff.update');

        // Assign staff role
        Route::patch(
            'staff/{staff}/role',
            [StaffController::class, 'assignRole']
        )->middleware('can:staff.assign-role');

        // Publishers
        Route::apiResource('publishers', PublisherController::class);

        // Packages
        Route::apiResource('packages', PackageController::class);

        // Settings
        Route::get('/settings/company', [SettingController::class, 'company']);
        Route::put('/settings/company', [SettingController::class, 'updateCompany']);

        Route::get('/settings/appearance', [SettingController::class, 'appearance']);
        Route::put('/settings/appearance', [SettingController::class, 'updateAppearance']);

        Route::get('/settings/invoice', [SettingController::class, 'invoice']);
        Route::put('/settings/invoice', [SettingController::class, 'updateInvoice']);

        Route::get('/settings/notification', [SettingController::class, 'notification']);
        Route::put('/settings/notification', [SettingController::class, 'updateNotification']);

        Route::get('/settings/smtp', [SettingController::class, 'smtp']);
        Route::put('/settings/smtp', [SettingController::class, 'updateSmtp']);

        Route::prefix('api/v1')->group(function () {
            Route::get('/settings/appearance', [SettingController::class, 'appearance']);
            Route::put('/settings/appearance', [SettingController::class, 'updateAppearance']);
            Route::get('/settings/invoice', [SettingController::class, 'invoice']);
            Route::put('/settings/invoice', [SettingController::class, 'updateInvoice']);
            Route::get('/settings/notification', [SettingController::class, 'notification']);
            Route::put('/settings/notification', [SettingController::class, 'updateNotification']);

            Route::get('/settings/smtp', [SettingController::class, 'smtp']);
            Route::put('/settings/smtp', [SettingController::class, 'updateSmtp']);
        });

        Route::apiResource('settings', SettingController::class);

        // Authors
        Route::apiResource('authors', AuthorController::class);

        // Categories
        Route::apiResource('categories', CategoryController::class);

        // Books
        Route::apiResource('books', BookController::class);

        // Add copies to an existing book
        Route::post(
            'books/{book}/copies',
            [BookController::class, 'addCopies']
        );

        // List all copies of a book
        Route::get(
            'books/{book}/copies',
            [BookController::class, 'listCopies']
        );

        // Show a single copy
        Route::get(
            'books/{book}/copies/{copy}',
            [BookController::class, 'showCopy']
        );

        // Update a single copy
        Route::put(
            'books/{book}/copies/{copy}',
            [BookController::class, 'updateCopy']
        );

        Route::patch(
            'books/{book}/copies/{copy}',
            [BookController::class, 'updateCopy']
        );

        // Delete a single copy
        Route::delete(
            'books/{book}/copies/{copy}',
            [BookController::class, 'deleteCopy']
        );

        // Book editions
        Route::apiResource(
            'book-editions',
            BookEditionController::class
        );

        // Book authors
        Route::apiResource(
            'book-authors',
            BookAuthorController::class
        );

        // Book categories
        Route::apiResource(
            'book-categories',
            BookCategoryController::class
        );

        // Copies
        Route::apiResource('copies', CopyController::class);

        // Borrows
        Route::apiResource('borrows', BorrowController::class);

        // Fines
        Route::apiResource('fines', FineController::class)->only([
            'index',
            'show',
            'destroy',
        ]);

        // Create fine for borrow
        Route::post(
            'borrows/{borrow}/fine',
            [FineController::class, 'createFineForBorrow']
        );

        // Create fine for seat booking
        Route::post(
            'booking-seats/{bookingSeat}/fine',
            [FineController::class, 'createFineForBookingSeat']
        );

        // Create fine for locker assignment
        Route::post(
            'locker-assignments/{lockerAssignment}/fine',
            [FineController::class, 'createFineForLocker']
        );

        /*
        |--------------------------------------------------------------------------
        | Bookings
        |--------------------------------------------------------------------------
        */

        Route::apiResource(
            'bookings',
            BookingController::class
        );

        // Booking seat records
        Route::get(
            'bookings/{booking}/seat-bookings',
            [BookingDetailsController::class, 'seatBookings']
        );

        // Booking borrow records
        Route::get(
            'bookings/{booking}/borrows',
            [BookingDetailsController::class, 'borrows']
        );

        // Booking locker assignments
        Route::get(
            'bookings/{booking}/locker-assignments',
            [BookingDetailsController::class, 'lockerAssignments']
        );

        // Complete seat booking
        Route::patch(
            'booking-seats/{seat}/complete',
            [BookingDetailsController::class, 'completeSeatBooking']
        );

        // All seat bookings
        Route::get(
            'booking-seats',
            [BookingDetailsController::class, 'allSeatBookings']
        );

        // Coupons
        Route::apiResource('coupons', CouponController::class);

        /*
        |--------------------------------------------------------------------------
        | Payments & Invoices
        |--------------------------------------------------------------------------
        */

        Route::apiResource(
            'payments',
            PaymentController::class
        )->only([
            'index',
            'store',
            'show',
            'destroy',
        ]);

        Route::apiResource(
            'invoices',
            InvoiceController::class
        )->only([
            'index',
            'show',
            'store',
        ]);

        // Add payment to invoice
        Route::post(
            'invoices/{invoice}/paymentss',
            [InvoiceController::class, 'addPayment']
        );

        // Get member invoice
        Route::get(
            'members/{member}/invoice',
            [InvoiceController::class, 'byMember']
        );

        // Get member fine invoice
        Route::get(
            'members/{member}/fine-invoice',
            [InvoiceController::class, 'fineInvoice']
        );

        /*
        |--------------------------------------------------------------------------
        | Facilities
        |--------------------------------------------------------------------------
        */

        // Floors
        Route::apiResource('floors', FloorController::class);

        // Rooms
        Route::apiResource('rooms', RoomController::class);

        // Seat categories
        Route::apiResource(
            'seat-categories',
            SeatCategoryController::class
        );

        // Seats
        Route::apiResource('seats', SeatController::class);

        // Lockers
        Route::apiResource('lockers', LockerController::class);

        // Locker assignments
        Route::apiResource(
            'locker-assignments',
            LockerAssigmentsController::class
        );

    });
});
