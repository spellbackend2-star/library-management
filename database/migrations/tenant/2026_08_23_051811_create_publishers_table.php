<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publishers', function (Blueprint $table) {
            $table->id();

            $table->string('name', 255);
            $table->string('address', 255)->nullable();
            $table->string('website', 500)->nullable();
            $table->string('contact_email', 255)->nullable();

            $table->timestamp('created_at')
                ->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publishers');
    }
};