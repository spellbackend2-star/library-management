<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_payments', function (Blueprint $table) {
            $table->string('payment_reference')
                ->nullable()
                ->unique()
                ->after('transaction_id');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_payments', function (Blueprint $table) {
            $table->dropUnique(['payment_reference']);
            $table->dropColumn('payment_reference');
        });
    }
};