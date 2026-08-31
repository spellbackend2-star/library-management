<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seat_bookings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('seat_id')
                ->constrained('seats')
                ->restrictOnDelete();

            $table->foreignId('member_id')
                ->constrained('members')
                ->restrictOnDelete();

            $table->dateTime('start_time');
            $table->dateTime('end_time');

          

            $table->enum('status', [
                'booked',
                'active',
                'completed',
                'cancelled',
                'no_show',
            ])->default('booked');

            $table->timestamps();

            $table->index('seat_id');
            $table->index('member_id');
            $table->index('start_time');
            $table->index('end_time');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seat_bookings');
    }
};