<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();

            $table->string('title', 500);
            $table->string('subtitle', 500)->nullable();

            $table->string('language', 50)
                ->default('English');

            $table->text('description')->nullable();

            $table->string('cover_image_url', 500)->nullable();

            $table->timestamp('created_at')
                ->useCurrent();

            $table->timestamp('deleted_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};