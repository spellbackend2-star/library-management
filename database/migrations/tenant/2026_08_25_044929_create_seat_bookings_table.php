<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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

            $table->foreignId('pricing_id')
                ->nullable()
                ->constrained('seat_pricing')
                ->nullOnDelete();

            $table->timestamp('start_time');

            $table->timestamp('end_time');

            $table->decimal('total_amount', 10, 2)
                ->default(0);

            $table->string('status', 30)
                ->default('confirmed');

            $table->timestamp('created_at')
                ->useCurrent();

            // Indexes
            $table->index([
                'seat_id',
                'start_time',
                'end_time',
            ]);

            $table->index('member_id');
        });

        DB::statement("
            ALTER TABLE seat_bookings
            ADD CONSTRAINT seat_bookings_time_check
            CHECK (start_time < end_time)
        ");

        DB::statement("
            ALTER TABLE seat_bookings
            ADD CONSTRAINT seat_bookings_amount_check
            CHECK (total_amount >= 0)
        ");

        DB::statement("
            ALTER TABLE seat_bookings
            ADD CONSTRAINT seat_bookings_status_check
            CHECK (
                status IN (
                    'confirmed',
                    'checked_in',
                    'completed',
                    'cancelled',
                    'no_show'
                )
            )
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('seat_bookings');
    }
};