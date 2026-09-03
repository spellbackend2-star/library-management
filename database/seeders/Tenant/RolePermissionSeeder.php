<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'staff.view',
            'staff.create',
            'staff.update',
            'staff.delete',
            'staff.assign-role',
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission],
                ['guard_name' => 'api']
            );
        }

        $roles = [
            'admin' => $permissions,
            'manager' => ['staff.view', 'staff.create', 'staff.update'],
            'librarian' => ['staff.view'],
            'clerk' => ['staff.view'],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::updateOrCreate(
                ['name' => $roleName],
                ['guard_name' => 'api']
            );
            $role->syncPermissions($rolePermissions);
        }
    }
}
