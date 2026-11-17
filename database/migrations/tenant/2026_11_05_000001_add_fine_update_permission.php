<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $now = now();
        DB::table('permissions')->updateOrInsert(
            ['name' => 'fines.update', 'guard_name' => 'api'],
            ['updated_at' => $now, 'created_at' => $now]
        );

        if (! Schema::hasTable('roles') || ! Schema::hasTable('role_has_permissions')) {
            return;
        }

        $permissionId = DB::table('permissions')
            ->where('name', 'fines.update')
            ->where('guard_name', 'api')
            ->value('id');

        $adminRoleId = DB::table('roles')
            ->where('name', 'admin')
            ->where('guard_name', 'api')
            ->value('id');

        if ($permissionId && $adminRoleId) {
            DB::table('role_has_permissions')->updateOrInsert([
                'permission_id' => $permissionId,
                'role_id' => $adminRoleId,
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $permissionId = DB::table('permissions')
            ->where('name', 'fines.update')
            ->where('guard_name', 'api')
            ->value('id');

        if ($permissionId && Schema::hasTable('role_has_permissions')) {
            DB::table('role_has_permissions')->where('permission_id', $permissionId)->delete();
        }

        DB::table('permissions')
            ->where('name', 'fines.update')
            ->where('guard_name', 'api')
            ->delete();
    }
};
