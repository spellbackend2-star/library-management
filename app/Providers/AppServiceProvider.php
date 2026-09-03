<?php

namespace App\Providers;

use App\Repositories\Eloquent\AuthorRepository;
use App\Repositories\Eloquent\BookAuthorRepository;
use App\Repositories\Eloquent\BookCategoryRepository;
use App\Repositories\Eloquent\BookEditionRepository;
use App\Repositories\Eloquent\BookingRepository;
use App\Repositories\Eloquent\BookingSeatRepository;
use App\Repositories\Eloquent\BookRepository;
use App\Repositories\Eloquent\BorrowRepository;
use App\Repositories\Eloquent\CategoryRepository;
use App\Repositories\Eloquent\CopyRepository;
use App\Repositories\Eloquent\CouponRepository;
use App\Repositories\Eloquent\FloorRepository;
use App\Repositories\Eloquent\LockerAssignmentRepository;
use App\Repositories\Eloquent\LockerRepository;
use App\Repositories\Eloquent\MembershipTypeRepository;
use App\Repositories\Eloquent\MemberRepository;
use App\Repositories\Eloquent\PackageRepository;
use App\Repositories\Eloquent\PaymentRepository;
use App\Repositories\Eloquent\PublisherRepository;
use App\Repositories\Eloquent\RoomRepository;
use App\Repositories\Eloquent\SeatBookingRepository;
use App\Repositories\Eloquent\SeatCategoryRepository;
use App\Repositories\Eloquent\SeatRepository;
use App\Repositories\Eloquent\StaffRepository;
use App\Repositories\Eloquent\TenantRepository;
use App\Repositories\Interface\AuthorInterface;
use App\Repositories\Interface\BookAuthorInterface;
use App\Repositories\Interface\BookCategoryInterface;
use App\Repositories\Interface\BookEditionInterface;
use App\Repositories\Interface\BookingInterface;
use App\Repositories\Interface\BookingSeatInterface;
use App\Repositories\Interface\BookInterface;
use App\Repositories\Interface\BorrowInterface;
use App\Repositories\Interface\CategoryInterface;
use App\Repositories\Interface\CopyInterface;
use App\Repositories\Interface\CouponInterface;
use App\Repositories\Interface\FloorInterface;
use App\Repositories\Interface\LockerAssignmentInterface;
use App\Repositories\Interface\LockerInterface;
use App\Repositories\Interface\MemberInterface;
use App\Repositories\Interface\MembershipTypeInterface;
use App\Repositories\Interface\PackageInterface;
use App\Repositories\Interface\PaymentRepositoryInterface;
use App\Repositories\Interface\PublisherInterface;
use App\Repositories\Interface\RoomInterface;
use App\Repositories\Interface\SeatBookingInterface;
use App\Repositories\Interface\SeatCategoryInterface;
use App\Repositories\Interface\SeatInterface;
use App\Repositories\Interface\StaffInterface;
use App\Repositories\Interface\TenantInterface;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            MembershipTypeInterface::class,
            MembershipTypeRepository::class
        );
        $this->app->bind(
            MemberInterface::class,
            MemberRepository::class
        );
        $this->app->bind(
            StaffInterface::class,
            StaffRepository::class
        );
        $this->app->bind(
            PublisherInterface::class,
            PublisherRepository::class
        );
        $this->app->bind(
            AuthorInterface::class,
            AuthorRepository::class
        );
        $this->app->bind(
            CategoryInterface::class,
            CategoryRepository::class
        );
        $this->app->bind(
            BookInterface::class,
            BookRepository::class
        );
        $this->app->bind(
            BookEditionInterface::class,
            BookEditionRepository::class
        );
        $this->app->bind(
            BookAuthorInterface::class,
            BookAuthorRepository::class
        );
        $this->app->bind(
            BookCategoryInterface::class,
            BookCategoryRepository::class
        );
        $this->app->bind(
            CopyInterface::class,
            CopyRepository::class
        );
        $this->app->bind(
            CouponInterface::class,
            CouponRepository::class
        );
        $this->app->bind(
            PackageInterface::class,
            PackageRepository::class
        );
        $this->app->bind(
            FloorInterface::class,
            FloorRepository::class
        );
        $this->app->bind(
            RoomInterface::class,
            RoomRepository::class
        );
        $this->app->bind(
            BorrowInterface::class,
            BorrowRepository::class
        );
        $this->app->bind(
            LockerInterface::class,
            LockerRepository::class
        );
        $this->app->bind(
            LockerAssignmentInterface::class,
            LockerAssignmentRepository::class
        );
        $this->app->bind(
            SeatInterface::class,
            SeatRepository::class
        );
        $this->app->bind(
            SeatCategoryInterface::class,
            SeatCategoryRepository::class
        );
        $this->app->bind(
            SeatBookingInterface::class,
            SeatBookingRepository::class
        );
        $this->app->bind(
            BookingInterface::class,
            BookingRepository::class
        );
         $this->app->bind(
            BookingSeatInterface::class,
            BookingSeatRepository::class
        );
         $this->app->bind(
            PaymentRepositoryInterface::class,
            PaymentRepository::class
        );
        $this->app->bind(
            TenantInterface::class,
            TenantRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Passport::loadKeysFrom(base_path('storage'));
        Passport::enablePasswordGrant();
    }
}
