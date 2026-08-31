<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lockers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('floor_id')
                ->constrained('floors')
                ->restrictOnDelete();

            $table->string('locker_number', 50)->unique();

            $table->string('locker_type', 50)->nullable();

            $table->string('location', 100)->nullable();

            $table->enum('status', [
                'available',
                'assigned',
                'maintenance',
                'out_of_service',
            ])->default('available');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lockers');
    }
};
