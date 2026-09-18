<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('subscriptions')
            ->whereNull('tenant_id')
            ->orWhere('tenant_id', '')
            ->delete();

        $tenantId = DB::table('tenants')->value('id');

        if ($tenantId) {
            DB::table('subscriptions')
                ->whereRaw('tenant_id IS NULL OR tenant_id = ""')
                ->update(['tenant_id' => $tenantId]);
        }

        if (Schema::hasColumn('subscriptions', 'tenant_id')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->char('tenant_id', 36)->nullable(false)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('subscriptions', 'tenant_id')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->char('tenant_id', 36)->nullable()->change();
            });
        }
    }
};
