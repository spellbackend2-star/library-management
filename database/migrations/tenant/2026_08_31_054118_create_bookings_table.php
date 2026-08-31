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

            $table->foreignId('member_id')
                ->constrained('members')
                ->restrictOnDelete();

            $table->foreignId('package_id')
                ->constrained('packages')
                ->restrictOnDelete();

            $table->enum('booking_type', [
                'seat',
                'book',
                'locker',
            ]);

            $table->enum('status', [
                'pending',
                'confirmed',
                'active',
                'completed',
                'cancelled',
            ])->default('pending');

            $table->decimal('amount', 10, 2)
                ->nullable();

            $table->text('notes')
                ->nullable();

            $table->timestamps();

            $table->index('member_id');
            $table->index('package_id');
            $table->index('booking_type');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
