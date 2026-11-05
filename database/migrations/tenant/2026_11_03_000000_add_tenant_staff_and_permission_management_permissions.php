<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $permissionNames = [
            'staff.view',
            'staff.create',
            'staff.update',
            'staff.delete',
            'staff.assign-role',
            'roles.view',
            'roles.create',
            'roles.update',
            'roles.delete',
            'permissions.view',
            'permissions.create',
            'permissions.update',
            'permissions.delete',
        ];

        $permissions = collect($permissionNames)
            ->map(fn (string $name): Permission => Permission::findOrCreate($name, 'api'));

        $adminRole = Role::query()
            ->where('name', 'admin')
            ->where('guard_name', 'api')
            ->first();

        if ($adminRole) {
            $adminRole->givePermissionTo($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Keep additive tenant admin permissions when rolling back this migration.
    }
};
