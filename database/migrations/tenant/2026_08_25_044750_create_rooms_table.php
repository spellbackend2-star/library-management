<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();

            $table->foreignId('floor_id')
                ->constrained('floors')
                ->restrictOnDelete();

            $table->string('name', 100);

            $table->enum('room_type', [
                'study_area',
                'quiet_zone',
                'group_room',
                'computer_lab',
            ])->default('study_area');

            $table->timestamps();

            $table->unique(['floor_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
