<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            ! Schema::hasTable('tenants')
            || ! Schema::hasColumn('tenants', 'status')
            || DB::connection()->getDriverName() !== 'mysql'
        ) {
            return;
        }

        DB::statement(
            "ALTER TABLE tenants MODIFY status ENUM('inactive', 'pending', 'active', 'suspended', 'rejected') NOT NULL DEFAULT 'inactive'"
        );
    }

    public function down(): void
    {
        if (
            ! Schema::hasTable('tenants')
            || ! Schema::hasColumn('tenants', 'status')
        ) {
            return;
        }

        DB::table('tenants')
            ->whereIn('status', ['pending', 'rejected'])
            ->update(['status' => 'inactive']);

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE tenants MODIFY status ENUM('inactive', 'active', 'suspended') NOT NULL DEFAULT 'inactive'"
            );
        }
    }
};
