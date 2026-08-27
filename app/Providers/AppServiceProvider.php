<?php

namespace App\Providers;

use App\Repositories\Eloquent\AuthorRepository;
use App\Repositories\Eloquent\BookAuthorRepository;
use App\Repositories\Eloquent\BookCategoryRepository;
use App\Repositories\Eloquent\BookEditionRepository;
use App\Repositories\Eloquent\BookRepository;
use App\Repositories\Eloquent\CategoryRepository;
use App\Repositories\Eloquent\CopyRepository;
use App\Repositories\Eloquent\MembershipTypeRepository;
use App\Repositories\Eloquent\MemberRepository;
use App\Repositories\Eloquent\PackageRepository;
use App\Repositories\Eloquent\PublisherRepository;
use App\Repositories\Eloquent\StaffRepository;
use App\Repositories\Interface\AuthorInterface;
use App\Repositories\Interface\BookAuthorInterface;
use App\Repositories\Interface\BookCategoryInterface;
use App\Repositories\Interface\BookEditionInterface;
use App\Repositories\Interface\BookInterface;
use App\Repositories\Interface\CategoryInterface;
use App\Repositories\Interface\CopyInterface;
use App\Repositories\Interface\MemberInterface;
use App\Repositories\Interface\MembershipTypeInterface;
use App\Repositories\Interface\PackageInterface;
use App\Repositories\Interface\PublisherInterface;
use App\Repositories\Interface\StaffInterface;
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
            PackageInterface::class,
             PackageRepository::class
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
