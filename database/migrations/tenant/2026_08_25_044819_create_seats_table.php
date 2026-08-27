<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seats', function (Blueprint $table) {
            $table->id();

            $table->foreignId('room_id')
                ->constrained('rooms')
                ->cascadeOnDelete();

            $table->foreignId('category_id')
                ->constrained('seat_categories')
                ->restrictOnDelete();

            $table->string('seat_number', 20);

            $table->boolean('has_power_outlet')
                ->default(false);

            $table->boolean('is_accessible')
                ->default(false);

            $table->string('status', 30)
                ->default('available');

            $table->timestamp('created_at')
                ->useCurrent();

            $table->unique(['room_id', 'seat_number']);

            // Indexes
            $table->index('room_id');
            $table->index('category_id');
        });

        DB::statement("
            ALTER TABLE seats
            ADD CONSTRAINT seats_status_check
            CHECK (
                status IN ('available', 'maintenance', 'out_of_service')
            )
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('seats');
    }
};