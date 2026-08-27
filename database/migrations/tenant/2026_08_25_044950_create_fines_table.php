<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('borrow_id')
                ->nullable()
                ->constrained('borrows')
                ->restrictOnDelete();

            $table->foreignId('seat_booking_id')
                ->nullable()
                ->constrained('seat_bookings')
                ->restrictOnDelete();

            $table->foreignId('member_id')
                ->constrained('members')
                ->restrictOnDelete();

            $table->decimal('amount', 10, 2)
                ->default(0);

            $table->string('reason', 30);

            $table->date('issued_date')
                ->useCurrent();

            $table->date('paid_date')
                ->nullable();

            $table->string('status', 20)
                ->default('unpaid');

            $table->timestamp('created_at')
                ->useCurrent();

            // Indexes
            $table->index('member_id');
            $table->index('borrow_id');
            $table->index('seat_booking_id');
        });

        DB::statement("
            ALTER TABLE fines
            ADD CONSTRAINT fines_amount_check
            CHECK (amount >= 0)
        ");

        DB::statement("
            ALTER TABLE fines
            ADD CONSTRAINT fines_reason_check
            CHECK (
                reason IN (
                    'overdue',
                    'damaged',
                    'lost',
                    'seat_no_show',
                    'late_cancellation'
                )
            )
        ");

        DB::statement("
            ALTER TABLE fines
            ADD CONSTRAINT fines_status_check
            CHECK (
                status IN (
                    'unpaid',
                    'paid',
                    'waived'
                )
            )
        ");

        DB::statement("
            ALTER TABLE fines
            ADD CONSTRAINT fines_source_check
            CHECK (
                (borrow_id IS NOT NULL AND seat_booking_id IS NULL)
                OR
                (borrow_id IS NULL AND seat_booking_id IS NOT NULL)
            )
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('fines');
    }
};