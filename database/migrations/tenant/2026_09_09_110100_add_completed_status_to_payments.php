<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('status', [
                'PENDING',
                'SUCCESS',
                'COMPLETED',
                'FAILED',
                'REFUNDED'
            ])->default('PENDING')->change();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('status', [
                'PENDING',
                'SUCCESS',
                'FAILED',
                'REFUNDED'
            ])->default('PENDING')->change();
        });
    }
};