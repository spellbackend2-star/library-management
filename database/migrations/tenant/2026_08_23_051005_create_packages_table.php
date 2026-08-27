<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();

            // Basic Information
            $table->string('name', 100)->unique();
            $table->text('description')->nullable();

            // Package Pricing & Duration
            $table->decimal('price', 10, 2)->default(0.00);
            $table->unsignedInteger('duration');
            $table->enum('duration_unit', [
                'day',
                'month',
                'year',
            ])->default('day');

            // Book Borrowing Limits
            $table->unsignedInteger('max_book_loans')->nullable();
            $table->unsignedInteger('max_borrow_days')->nullable();

            // Seat Access & Daily Limit
            $table->boolean('seat_access_allowed')->default(true);
            $table->decimal('max_seat_hours_per_day', 4, 2)->nullable();

            // Locker Access & Daily Limit
            $table->boolean('locker_allowed')->default(false);
            $table->string('locker_type', 50)->nullable();
            $table->decimal('max_locker_hours_per_day', 4, 2)->nullable();

            // Status
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Indexes
            $table->index('is_active');
            $table->index(['duration', 'duration_unit']);
        });
        // Price CHECK constraint
        DB::statement("
            ALTER TABLE packages
            ADD CONSTRAINT packages_price_check
            CHECK (price >= 0)
        ");

        // Duration CHECK constraint
        DB::statement("
            ALTER TABLE packages
            ADD CONSTRAINT packages_duration_check
            CHECK (duration > 0)
        ");

        // Max book loans CHECK constraint
        DB::statement("
            ALTER TABLE packages
            ADD CONSTRAINT packages_max_book_loans_check
            CHECK (
                max_book_loans IS NULL
                OR max_book_loans >= 0
            )
        ");

        // Max borrow days CHECK constraint
        DB::statement("
            ALTER TABLE packages
            ADD CONSTRAINT packages_max_borrow_days_check
            CHECK (
                max_borrow_days IS NULL
                OR max_borrow_days >= 0
            )
        ");

        // Seat hours CHECK constraint
        DB::statement("
            ALTER TABLE packages
            ADD CONSTRAINT packages_seat_hours_check
            CHECK (
                max_seat_hours_per_day IS NULL
                OR (
                    max_seat_hours_per_day > 0
                    AND max_seat_hours_per_day <= 24.00
                )
            )
        ");

        // Locker hours CHECK constraint
        DB::statement("
            ALTER TABLE packages
            ADD CONSTRAINT packages_locker_hours_check
            CHECK (
                max_locker_hours_per_day IS NULL
                OR (
                    max_locker_hours_per_day > 0
                    AND max_locker_hours_per_day <= 24.00
                )
            )
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
