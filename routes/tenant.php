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

    // Tenant login
    Route::post('/login', [
        AuthController::class,
        'login'
    ]);

    // PROTECTED
    Route::middleware('auth:api')->group(function () {

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
            'packages',
            PackageController::class
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

        // Add copies to an existing book
        Route::post(
            'books/{book}/copies',
            [BookController::class, 'addCopies']
        );

        // List all copies of a book (across all editions)
        Route::get(
            'books/{book}/copies',
            [BookController::class, 'listCopies']
        );

        // Show a single copy that belongs to the book
        Route::get(
            'books/{book}/copies/{copy}',
            [BookController::class, 'showCopy']
        );

        // Update a single copy that belongs to the book
        Route::put(
            'books/{book}/copies/{copy}',
            [BookController::class, 'updateCopy']
        );
        Route::patch(
            'books/{book}/copies/{copy}',
            [BookController::class, 'updateCopy']
        );

        // Delete a single copy that belongs to the book
        Route::delete(
            'books/{book}/copies/{copy}',
            [BookController::class, 'deleteCopy']
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

        Route::apiResource(
            'borrows',
            BorrowController::class
        );

        Route::apiResource(
            'bookings',
            BookingController::class
        );

        // Per-booking child records (saved together with the booking)
        Route::get(
            'bookings/{booking}/seat-bookings',
            [BookingDetailsController::class, 'seatBookings']
        );
        Route::get(
            'bookings/{booking}/borrows',
            [BookingDetailsController::class, 'borrows']
        );
        Route::get(
            'bookings/{booking}/locker-assignments',
            [BookingDetailsController::class, 'lockerAssignments']
        );

        // Mark a single seat-booking as completed (triggers overdue fine if late)
        Route::patch(
            'booking-seats/{seat}/complete',
            [BookingDetailsController::class, 'completeSeatBooking']
        );

        Route::apiResource(
            'coupons',
            CouponController::class
        );

        Route::apiResource(
            'payments',
            PaymentController::class
        )->only(['index', 'store', 'show', 'destroy']);

        Route::apiResource(
            'floors',
            FloorController::class
        );

        Route::apiResource(
            'rooms',
            RoomController::class
        );

        Route::apiResource(
            'seat-categories',
            SeatCategoryController::class
        );

        Route::apiResource(
            'seats',
            SeatController::class
        );

        // All seat-bookings saved inside bookings (data is in the
        // `booking_seats` table; the legacy `seat_bookings` table is
        // not used and was never created by tenant migrations).
        Route::get(
            'booking-seats',
            [BookingDetailsController::class, 'allSeatBookings']
        );

        Route::apiResource(
            'lockers',
            LockerController::class
        );

        Route::apiResource(
            'locker-assignments',
            LockerAssigmentsController::class
        );
    });
});
