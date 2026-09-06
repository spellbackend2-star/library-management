<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('invoice_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('payment_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->index('invoice_id');
            $table->index('payment_id');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
            $table->dropForeign(['payment_id']);
            $table->dropIndex(['invoice_id']);
            $table->dropIndex(['payment_id']);
            $table->dropColumn(['invoice_id', 'payment_id']);
        });
    }
};
