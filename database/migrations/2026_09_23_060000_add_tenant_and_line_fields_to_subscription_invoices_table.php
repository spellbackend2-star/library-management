<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('subscription_invoices', 'tenant_id')) {
                $table->string('tenant_id', 36)
                    ->nullable()
                    ->after('subscription_id')
                    ->references('id')
                    ->on('tenants')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('subscription_invoices', 'subtotal')) {
                $table->decimal('subtotal', 12, 2)
                    ->default(0)
                    ->after('total_amount');
            }

            if (!Schema::hasColumn('subscription_invoices', 'tax')) {
                $table->decimal('tax', 12, 2)
                    ->default(0)
                    ->after('subtotal');
            }

            if (!Schema::hasColumn('subscription_invoices', 'discount')) {
                $table->decimal('discount', 12, 2)
                    ->default(0)
                    ->after('tax');
            }

            if (Schema::hasColumn('subscription_invoices', 'tenant_id') && !Schema::hasIndex('subscription_invoices', 'subscription_invoices_tenant_id_index')) {
                $table->index('tenant_id');
            }
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