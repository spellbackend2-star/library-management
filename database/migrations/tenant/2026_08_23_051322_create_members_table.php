<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();

            $table->foreignId('membership_type_id')
                ->constrained('membership_types')
                ->restrictOnDelete();

            $table->string('first_name', 100);
            $table->string('last_name', 100);

            $table->string('email', 255)->unique();
            $table->string('phone', 30)->nullable();
            $table->string('address', 255)->nullable();

            $table->date('date_of_birth')->nullable();

            $table->date('membership_start')
                ->useCurrent();

            $table->date('membership_expiry')->nullable();

            $table->string('status', 20)
                ->default('active');

            $table->timestamp('created_at')
                ->useCurrent();

            $table->timestamp('updated_at')
                ->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};