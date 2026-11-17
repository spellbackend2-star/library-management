
<?php


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
use App\Http\Controllers\v1\Tenant\RoleController;
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

Route::prefix('api/v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Public Routes
    |--------------------------------------------------------------------------
    */

    // Authentication
    Route::post('login', [AuthController::class, 'login']);

    // Khalti Payment Verification
    Route::get(
        '/payments/khalti/verify/{paymentId}',
        [PaymentController::class, 'verifyKhalti']
    )->name('payments.khalti.verify');

    // eSewa Payment Verification
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

            /*
            |--------------------------------------------------------------------------
            | Profile & Authentication
            |--------------------------------------------------------------------------
            */

            Route::get('/profile/me', [AuthController::class, 'me']);
            Route::get('/profile', [AuthController::class, 'profile']);
            Route::put('/profile', [AuthController::class, 'update']);

            Route::post('/profile/change-password', [
                AuthController::class,
                'changePassword',
            ]);
            Route::match(['post', 'put'], '/profile/password', [AuthController::class, 'changePassword']);

            Route::post('/logout', [AuthController::class, 'logout']);


            /*
            |--------------------------------------------------------------------------
            | Members
            |--------------------------------------------------------------------------
            */

            Route::get('members/all', [MemberController::class, 'all']);
            Route::apiResource('members', MemberController::class);


            /*
            |--------------------------------------------------------------------------
            | Staff Management
            |--------------------------------------------------------------------------
            */

            Route::get('staff/me', [StaffController::class, 'me']);
            Route::get('staff/profile', [StaffController::class, 'profile']);

            Route::apiResource('staff', StaffController::class)
                ->middlewareFor('index', 'can:staff.view')
                ->middlewareFor('show', 'can:staff.view')
                ->middlewareFor('store', 'can:staff.create')
                ->middlewareFor('update', 'can:staff.update')
                ->middlewareFor('destroy', 'can:staff.delete');

            Route::patch(
                'staff/{staff}/activate',
                [StaffController::class, 'activate']
            )->middleware('can:staff.update');

            Route::patch(
                'staff/{staff}/deactivate',
                [StaffController::class, 'deactivate']
            )->middleware('can:staff.update');

            Route::patch(
                'staff/{staff}/role',
                [StaffController::class, 'assignRole']
            )->middleware('can:staff.assign-role');

            // Tenant role and permission management
            Route::get('roles/all', [RoleController::class, 'all'])->middleware('tenant.role-management:roles.view');
            Route::get('roles', [RoleController::class, 'index'])->middleware('tenant.role-management:roles.view');
            Route::get('roles/{role}', [RoleController::class, 'show'])->middleware('tenant.role-management:roles.view');
            Route::post('roles', [RoleController::class, 'store'])->middleware('tenant.role-management:roles.create');
            Route::patch('roles/{role}', [RoleController::class, 'update'])->middleware('tenant.role-management:roles.update');
            Route::delete('roles/{role}', [RoleController::class, 'destroy'])->middleware('tenant.role-management:roles.delete');

            Route::get('permissions', [RoleController::class, 'permissions'])->middleware('tenant.role-management:roles.view');
            Route::post('permissions', [RoleController::class, 'storePermission'])->middleware('tenant.role-management:roles.create');
            Route::patch('permissions/{permission}', [RoleController::class, 'updatePermission'])->middleware('tenant.role-management:roles.update');
            Route::delete('permissions/{permission}', [RoleController::class, 'destroyPermission'])->middleware('tenant.role-management:roles.delete');


            /*
            |--------------------------------------------------------------------------
            | Publishers
            |--------------------------------------------------------------------------
            */

            Route::get('publishers/all', [PublisherController::class, 'all']);
            Route::apiResource('publishers', PublisherController::class);


            /*
            |--------------------------------------------------------------------------
            | Packages
            |--------------------------------------------------------------------------
            */

            Route::get('packages/all', [PackageController::class, 'all']);
            Route::apiResource('packages', PackageController::class);


            /*
            |--------------------------------------------------------------------------
            | Settings
            |--------------------------------------------------------------------------
            */

            // Company Settings
            Route::get('settings/company', [SettingController::class, 'company']);
            Route::put('settings/company', [SettingController::class, 'updateCompany']);

            // Appearance Settings
            Route::get('settings/appearance', [SettingController::class, 'appearance']);
            Route::put('settings/appearance', [SettingController::class, 'updateAppearance']);

            // Invoice Settings
            Route::get('settings/invoice', [SettingController::class, 'invoice']);
            Route::put('settings/invoice', [SettingController::class, 'updateInvoice']);

            Route::get('settings/book-fine-rate', [SettingController::class, 'bookFineRate'])
                ->middleware('can:settings.view');
            Route::put('settings/book-fine-rate', [SettingController::class, 'updateBookFineRate'])
                ->middleware('can:settings.update');
            Route::post('settings/book-fine-rate', [SettingController::class, 'updateBookFineRate'])
                ->middleware('can:settings.update');

            Route::get('settings/staff-book-rate', [SettingController::class, 'staffBookRate'])
                ->middleware('can:settings.view');
            Route::put('settings/staff-book-rate', [SettingController::class, 'updateStaffBookRate'])
                ->middleware('can:settings.update');

            Route::get('settings/staff-book-fine-rate', [SettingController::class, 'staffBookFineRate'])
                ->middleware('can:settings.view');
            Route::put('settings/staff-book-fine-rate', [SettingController::class, 'updateStaffBookFineRate'])
                ->middleware('can:settings.update');

            // Notification Settings
            Route::get('settings/notification', [SettingController::class, 'notification']);
            Route::put('settings/notification', [SettingController::class, 'updateNotification']);

            // SMTP Settings
            Route::get('settings/smtp', [SettingController::class, 'smtp']);
            Route::put('settings/smtp', [SettingController::class, 'updateSmtp']);

            // Settings CRUD
            Route::apiResource('settings', SettingController::class);


            /*
            |--------------------------------------------------------------------------
            | Authors
            |--------------------------------------------------------------------------
            */

            Route::get('authors/all', [AuthorController::class, 'all']);
            Route::apiResource('authors', AuthorController::class);


            /*
            |--------------------------------------------------------------------------
            | Categories
            |--------------------------------------------------------------------------
            */

            Route::get('categories/all', [CategoryController::class, 'all']);
            Route::apiResource('categories', CategoryController::class);


            /*
            |--------------------------------------------------------------------------
            | Books
            |--------------------------------------------------------------------------
            */

            Route::apiResource('books', BookController::class);

            // Add Copies to an Existing Book
            Route::post(
                'books/{book}/copies',
                [BookController::class, 'addCopies']
            );

            // List All Copies of a Book
            Route::get(
                'books/{book}/copies',
                [BookController::class, 'listCopies']
            );

            // Show a Single Book Copy
            Route::get(
                'books/{book}/copies/{copy}',
                [BookController::class, 'showCopy']
            );

            // Update a Single Book Copy
            Route::put(
                'books/{book}/copies/{copy}',
                [BookController::class, 'updateCopy']
            );

            Route::patch(
                'books/{book}/copies/{copy}',
                [BookController::class, 'updateCopy']
            );

            // Delete a Single Book Copy
            Route::delete(
                'books/{book}/copies/{copy}',
                [BookController::class, 'deleteCopy']
            );


            /*
            |--------------------------------------------------------------------------
            | Book Management
            |--------------------------------------------------------------------------
            */

            Route::apiResource('book-editions', BookEditionController::class);
            Route::apiResource('book-authors', BookAuthorController::class);
            Route::apiResource('book-categories', BookCategoryController::class);
            Route::apiResource('copies', CopyController::class);


            /*
            |--------------------------------------------------------------------------
            | Borrow Management
            |--------------------------------------------------------------------------
            */

            Route::apiResource('borrows', BorrowController::class);


            /*
            |--------------------------------------------------------------------------
            | Fine Management
            |--------------------------------------------------------------------------
            */

            Route::apiResource('fines', FineController::class)->only([
                'index',
                'show',
                'destroy',
            ])->middlewareFor('index', 'can:fines.view')
                ->middlewareFor('show', 'can:fines.view')
                ->middlewareFor('destroy', 'can:fines.delete');

            Route::post('fines/manual', [FineController::class, 'createManual'])
                ->middleware('can:fines.create');

            Route::patch('fines/{id}/amount', [FineController::class, 'updateAmount'])
                ->middleware('can:fines.update');

            Route::post('fines/{id}/cancel', [FineController::class, 'cancel'])
                ->middleware('can:fines.delete');


            Route::post(
                'borrows/{borrow}/fine',
                [FineController::class, 'createFineForBorrow']
            );

            /*
            |--------------------------------------------------------------------------
            | Booking Management
            |--------------------------------------------------------------------------
            */

            Route::post('admin/bookings', [BookingController::class, 'store']);

            // Booking Seat Records
            Route::get(
                'bookings/{booking}/seat-bookings',
                [BookingDetailsController::class, 'seatBookings']
            );

            // Booking Borrow Records
            Route::get(
                'bookings/{booking}/borrows',
                [BookingDetailsController::class, 'borrows']
            );

            // Booking Locker Assignments
            Route::get(
                'bookings/{booking}/locker-assignments',
                [BookingDetailsController::class, 'lockerAssignments']
            );

            // Complete Seat Booking
            Route::patch(
                'booking-seats/{seat}/complete',
                [BookingDetailsController::class, 'completeSeatBooking']
            );

            // All Seat Bookings
            Route::get(
                'booking-seats',
                [BookingDetailsController::class, 'allSeatBookings']
            );

            Route::get(
                'booking-seats/{seat}',
                [BookingDetailsController::class, 'showSeatBooking']
            );

            Route::get(
                'booking-seatsget',
                [BookingDetailsController::class, 'bookedSeatBookings']
            );


            /*
            |--------------------------------------------------------------------------
            | Coupons
            |--------------------------------------------------------------------------
            */

            Route::apiResource('coupons', CouponController::class);


            /*
            |--------------------------------------------------------------------------
            | Payments & Invoices
            |--------------------------------------------------------------------------
            */

            Route::apiResource('payments', PaymentController::class)->only([
                'index',
                'store',
                'show',
                'destroy',
            ]);

            Route::apiResource('invoices', InvoiceController::class)->only([
                'index',
                'show',
                'store',
            ]);

            // Add Payment to Invoice
            Route::post(
                'invoices/{invoice}/payments',
                [InvoiceController::class, 'addPayment']
            );

            // Get Member Invoice
            Route::get(
                'members/{member}/invoice',
                [InvoiceController::class, 'byMember']
            );

            // Get Member Fine Invoice
            Route::get(
                'members/{member}/fine-invoice',
                [InvoiceController::class, 'fineInvoice']
            );


            /*
            |--------------------------------------------------------------------------
            | Facilities Management
            |--------------------------------------------------------------------------
            */

            // Floors
            Route::get('floors/all', [FloorController::class, 'all']);
            Route::apiResource('floors', FloorController::class);

            // Rooms
            Route::get('rooms/all', [RoomController::class, 'all']);
            Route::apiResource('rooms', RoomController::class);

            // Seat Categories
            Route::get('seat-categories/all', [SeatCategoryController::class, 'all']);
            Route::apiResource('seat-categories', SeatCategoryController::class);

            // Seats
            Route::apiResource('seats', SeatController::class);

            // Lockers
            Route::get('lockers/all', [LockerController::class, 'all']);
            Route::apiResource('lockers', LockerController::class);

            // Locker Assignments
            Route::apiResource(
                'locker-assignments',
                LockerAssigmentsController::class
            );

        });
});
});
