<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('subscriptions', 'tenant_id')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->uuid('tenant_id')->nullable()->after('id');
            });
        }

        $fallbackTenantId = DB::table('tenants')->value('id');

        if ($fallbackTenantId) {
            DB::table('subscriptions')
                ->where(function ($query) {
                    $query->whereNull('tenant_id')
                        ->orWhere('tenant_id', '');
                })
                ->update(['tenant_id' => $fallbackTenantId]);
        }

        if (DB::table('subscriptions')->whereNull('tenant_id')->exists()) {
            throw new \RuntimeException('Subscriptions table still contains null tenant_id values.');
        }

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('subscriptions', 'tenant_id')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->dropForeign(['tenant_id']);
                $table->dropIndex(['tenant_id', 'status']);
                $table->dropColumn('tenant_id');
            });
        }
    }
};