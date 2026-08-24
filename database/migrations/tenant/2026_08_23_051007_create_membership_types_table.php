<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_types', function (Blueprint $table) {
            $table->id();

            $table->string('name', 50)->unique();
            $table->text('description')->nullable();

            $table->integer('max_book_loans')
                ->default(5);

            $table->integer('max_seat_hours_per_day')
                ->default(2);

            $table->decimal('annual_fee', 10, 2)
                ->default(0);

            $table->timestamp('created_at')
                ->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_types');
    }
};