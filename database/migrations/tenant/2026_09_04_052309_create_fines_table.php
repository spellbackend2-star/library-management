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

            // Fine source: Book borrowing
            $table->foreignId('borrow_id')
                ->nullable()
                ->constrained('borrows')
                ->restrictOnDelete();

            // Fine source: Seat booking
            $table->foreignId('booking_seat_id')
                ->nullable()
                ->constrained('booking_seats')
                ->restrictOnDelete();

            // Fine source: Locker assignment
            $table->foreignId('locker_assignment_id')
                ->nullable()
                ->constrained('locker_assignments')
                ->restrictOnDelete();

            // Member who received the fine
            $table->foreignId('member_id')
                ->constrained('members')
                ->restrictOnDelete();

            // Fine amount
            $table->decimal('amount', 10, 2)
                ->default(0);

            // Reason for fine
            $table->enum('reason', [
                'overdue',
                'damaged',
                'lost',
                'locker_overdue',
                'seat_overdue',
            ]);

            // Fine issue date
            $table->date('issued_date')
                ->useCurrent();

            // Payment date
            $table->date('paid_date')
                ->nullable();

            // Fine status
            $table->enum('status', [
                'unpaid',
                'paid',
                'waived',
            ])->default('unpaid');

            $table->timestamps();

            // Indexes
            $table->index('borrow_id');
            $table->index('booking_seat_id');
            $table->index('locker_assignment_id');
            $table->index('member_id');
            $table->index('reason');
            $table->index('status');
            $table->index('issued_date');
        });

        /*
         * A fine must belong to exactly ONE source:
         *
         * borrow_id
         * OR booking_seat_id
         * OR locker_assignment_id
         */
        DB::statement("
            ALTER TABLE fines
            ADD CONSTRAINT fine_source_check
            CHECK (
                (borrow_id IS NOT NULL) +
                (booking_seat_id IS NOT NULL) +
                (locker_assignment_id IS NOT NULL) = 1
            )
        ");

        // Amount cannot be negative
        DB::statement("
            ALTER TABLE fines
            ADD CONSTRAINT fine_amount_check
            CHECK (amount >= 0)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('fines');
    }
};