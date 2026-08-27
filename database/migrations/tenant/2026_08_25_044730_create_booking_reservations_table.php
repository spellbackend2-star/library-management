<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_reservations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('book_id')
                ->constrained('books')
                ->restrictOnDelete();

            $table->foreignId('member_id')
                ->constrained('members')
                ->cascadeOnDelete();

            $table->timestamp('reservation_date')
                ->useCurrent();

            $table->date('expiry_date')
                ->nullable();

            $table->string('status', 30)
                ->default('pending');

            $table->timestamp('created_at')
                ->useCurrent();

            // Indexes
            $table->index('book_id');
            $table->index('member_id');
            $table->index('status');
        });

        DB::statement("
            ALTER TABLE book_reservations
            ADD CONSTRAINT book_reservations_status_check
            CHECK (
                status IN (
                    'pending',
                    'ready_for_pickup',
                    'fulfilled',
                    'cancelled',
                    'expired'
                )
            )
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('book_reservations');
    }
};