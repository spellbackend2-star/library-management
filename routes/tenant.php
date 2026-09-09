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
            'invoices/{invoice}/payments',
            [InvoiceController::class, 'addPayment']
        );

        // Get member invoice
        Route::get(
            'members/{member}/invoice',
            [InvoiceController::class, 'byMember']
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
