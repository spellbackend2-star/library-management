<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('copies', function (Blueprint $table) {
            $table->id();

            
            $table->foreignId('edition_id')
                ->constrained('book_editions')
                ->restrictOnDelete();
            $table->string('barcode', 100)->unique();

            $table->string('shelf_location', 100)->nullable();

            $table->enum('condition', [
                'new',
                'good',
                'fair',
                'damaged',
            ])->default('good');

            $table->enum('status', [
                'available',
                'on_loan',
                'reserved',
                'withdrawn',
            ])->default('available');

            $table->date('acquisition_date')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('copies');
    }
};
