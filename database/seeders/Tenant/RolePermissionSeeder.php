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
            'profile.view',
            'profile.update',
            'profile.change-password',
            'staff.view',
            'staff.create',
            'staff.update',
            'staff.delete',
            'staff.assign-role',
            'roles.view',
            'roles.create',
            'roles.update',
            'roles.delete',
            'members.view',
            'members.create',
            'members.update',
            'members.delete',
            'publishers.view',
            'publishers.create',
            'publishers.update',
            'publishers.delete',
            'packages.view',
            'packages.create',
            'packages.update',
            'packages.delete',
            'settings.view',
            'settings.update',
            'authors.view',
            'authors.create',
            'authors.update',
            'authors.delete',
            'categories.view',
            'categories.create',
            'categories.update',
            'categories.delete',
            'books.view',
            'books.create',
            'books.update',
            'books.delete',
            'book-editions.view',
            'book-editions.create',
            'book-editions.update',
            'book-editions.delete',
            'book-authors.view',
            'book-authors.create',
            'book-authors.update',
            'book-authors.delete',
            'book-categories.view',
            'book-categories.create',
            'book-categories.update',
            'book-categories.delete',
            'copies.view',
            'copies.create',
            'copies.update',
            'copies.delete',
            'borrows.view',
            'borrows.create',
            'borrows.update',
            'borrows.delete',
            'fines.view',
            'fines.create',
            'fines.update',
            'fines.delete',
            'bookings.view',
            'bookings.create',
            'bookings.update',
            'bookings.delete',
            'booking-seats.view',
            'booking-seats.complete',
            'coupons.view',
            'coupons.create',
            'coupons.update',
            'coupons.delete',
            'payments.view',
            'payments.create',
            'payments.delete',
            'invoices.view',
            'invoices.create',
            'invoices.add-payment',
            'floors.view',
            'floors.create',
            'floors.update',
            'floors.delete',
            'rooms.view',
            'rooms.create',
            'rooms.update',
            'rooms.delete',
            'seat-categories.view',
            'seat-categories.create',
            'seat-categories.update',
            'seat-categories.delete',
            'seats.view',
            'seats.create',
            'seats.update',
            'seats.delete',
            'lockers.view',
            'lockers.create',
            'lockers.update',
            'lockers.delete',
            'locker-assignments.view',
            'locker-assignments.create',
            'locker-assignments.update',
            'locker-assignments.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission],
                ['guard_name' => 'api']
            );
        }

        $admin = Role::updateOrCreate(
            ['name' => 'admin'],
            ['guard_name' => 'api']
        );
        $admin->syncPermissions(
            Permission::query()->where('guard_name', 'api')->get()
        );

        $roles = [
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
