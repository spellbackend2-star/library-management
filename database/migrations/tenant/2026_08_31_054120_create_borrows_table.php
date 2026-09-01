<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('borrows', function (Blueprint $table) {
            $table->id();

            // Package booking that created this borrow
            $table->foreignId('booking_id')
                ->nullable()
                ->constrained('bookings')
                ->nullOnDelete();

            $table->foreignId('copy_id')
                ->constrained('copies')
                ->restrictOnDelete();

            $table->foreignId('member_id')
                ->constrained('members')
                ->restrictOnDelete();

        
          

            $table->timestamp('checkout_date')
                ->useCurrent();

            $table->date('due_date');

            $table->timestamp('return_date')
                ->nullable();

            $table->unsignedInteger('renewal_count')
                ->default(0);

            $table->string('status', 20)
                ->default('active');

            $table->timestamps();

            // Indexes
            $table->index('booking_id');
            $table->index('member_id');
            $table->index('copy_id');
            $table->index('status');
            $table->index('due_date');
        });

        DB::statement("
            ALTER TABLE borrows
            ADD CONSTRAINT borrows_status_check
            CHECK (status IN ('active', 'returned', 'overdue', 'lost'))
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('borrows');
    }
};

