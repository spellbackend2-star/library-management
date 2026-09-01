<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            // Member who owns the booking
            $table->foreignId('member_id')
                ->constrained('members')
                ->restrictOnDelete();

            // User/staff who created the booking
            $table->foreignId('booked_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Package used for the booking
            $table->foreignId('package_id')
                ->constrained('packages')
                ->restrictOnDelete();

            // seat / book / locker
            $table->enum('booking_type', [
                'seat',
                'book',
                'locker','package',
            ])->default('package');

            // Pricing
            $table->decimal('amount', 10, 2)->default(0);
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('convenience_fee', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);

            // Coupon
            $table->foreignId('coupon_id')
                ->nullable()
                ->constrained('coupons')
                ->nullOnDelete();

            // Booking status
            $table->enum('status', [
                'PENDING',
                'CONFIRMED',
                'ACTIVE',
                'COMPLETED',
                'CANCELLED',
                'EXPIRED',
            ])->default('PENDING');

            // Payment status
            $table->enum('payment_status', [
                'UNPAID',
                'PAID',
                'PARTIALLY_REFUNDED',
                'REFUNDED',
            ])->default('UNPAID');

            // How booking was created
            $table->enum('booking_source', [
                'WEB',
                'COUNTER',
            ])->default('WEB');

            $table->text('notes')->nullable();

            $table->dateTime('expires_at')->nullable();
            $table->dateTime('confirmed_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('member_id');
            $table->index('package_id');
            $table->index('booking_type');
            $table->index('status');
            $table->index('payment_status');
            $table->index('booking_source');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};