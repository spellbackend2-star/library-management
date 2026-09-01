<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locker_assignments', function (Blueprint $table) {
            $table->id();
              $table->foreignId('booking_id')
                ->nullable()
                ->constrained('bookings')
                ->nullOnDelete();

            $table->index('booking_id');

            $table->foreignId('locker_id')
                ->constrained('lockers')
                ->restrictOnDelete();

            $table->foreignId('member_id')
                ->constrained('members')
                ->restrictOnDelete();

            $table->date('assigned_date');

            $table->date('expiry_date');

            $table->date('returned_date')
                ->nullable();

            $table->enum('status', [
                'active',
                'expired',
                'returned',
                'cancelled',
            ])->default('active');

            $table->timestamps();

            $table->index('locker_id');
            $table->index('member_id');
            $table->index('status');
            $table->index('expiry_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locker_assignments');
    }
};