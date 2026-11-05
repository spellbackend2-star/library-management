<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect(['roles.view', 'roles.create', 'roles.update', 'roles.delete'])
            ->map(fn (string $name) => Permission::findOrCreate($name, 'api'));

        $admin = Role::query()->where('guard_name', 'api')->where('name', 'admin')->first();
        $admin?->givePermissionTo($permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Keep seeded management permissions intact when rolling back this data migration.
    }
};
