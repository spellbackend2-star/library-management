<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_invoices', function (Blueprint $table) {
            $table->string('tenant_id', 36)
                ->nullable()
                ->after('subscription_id')
                ->references('id')
                ->on('tenants')
                ->nullOnDelete();

            $table->decimal('subtotal', 12, 2)
                ->default(0)
                ->after('total_amount');

            $table->decimal('tax', 12, 2)
                ->default(0)
                ->after('subtotal');

            $table->decimal('discount', 12, 2)
                ->default(0)
                ->after('tax');

            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_invoices', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropColumn(['tenant_id', 'subtotal', 'tax', 'discount']);
        });
    }
};