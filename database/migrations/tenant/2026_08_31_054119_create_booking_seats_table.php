<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_seats', function (Blueprint $table) {
            $table->id();

            $table->foreignId('booking_id')
                ->constrained('bookings')
                ->cascadeOnDelete();
            $table->foreignId('member_id')
                ->constrained('members')
                ->restrictOnDelete();
            $table->foreignId('seat_id')
                ->constrained('seats')
                ->restrictOnDelete();

            $table->dateTime('start_at');
            $table->dateTime('end_at');

            $table->enum('status', [
                'booked',
                'active',
                'completed',
                'cancelled',
                'no_show',
            ])->default('booked');

            $table->timestamps();

            $table->index('booking_id');
            $table->index('seat_id');
            $table->index('start_at');
            $table->index('end_at');
            $table->index('status');
        });

        DB::statement("
            ALTER TABLE booking_seats
            ADD CONSTRAINT booking_seats_status_check
            CHECK (status IN ('booked', 'active', 'completed', 'cancelled', 'no_show'))
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_seats');
    }
};
