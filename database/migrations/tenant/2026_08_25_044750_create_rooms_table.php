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

            $table->string('name', 100)
                ->unique();

            $table->string('room_type', 50)
                ->default('study_area');

            $table->timestamp('created_at')
                ->useCurrent();
        });

        DB::statement("
            ALTER TABLE rooms
            ADD CONSTRAINT rooms_room_type_check
            CHECK (
                room_type IN (
                    'study_area',
                    'quiet_zone',
                    'group_room',
                    'computer_lab'
                )
            )
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};