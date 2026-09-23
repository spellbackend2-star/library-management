<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class CentralRolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Clear Spatie's cached permissions.
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        /*
        |--------------------------------------------------------------------------
        | Central Permissions
        |--------------------------------------------------------------------------
        */

        $permissions = [
            // Dashboard
            'dashboard.view',

            // Users
            'users.view',
            'users.create',
            'users.update',
            'users.delete',

            // Tenants
            'tenants.view',
            'tenants.create',
            'tenants.update',
            'tenants.delete',

            // Subscription Plans
            'subscription_plans.view',
            'subscription_plans.create',
            'subscription_plans.update',
            'subscription_plans.delete',

            // Subscriptions
            'subscriptions.view',
            'subscriptions.create',
            'subscriptions.update',
            'subscriptions.cancel',

            // Subscription Payments
            'subscription_payments.view',
            'subscription_payments.create',
            'subscription_payments.update',

            // Tickets
            'tickets.view',
            'tickets.create',
            'tickets.reply',
            'tickets.assign',
            'tickets.update',
            'tickets.close',
            'tickets.delete',

            // Notifications
            'notifications.view',
            'notifications.send',

            // Settings
            'settings.view',
            'settings.update',

            // Invoices
            'invoices.view',
            'invoices.create',
            'invoices.update',
            'invoices.delete',

            // Payments
            'payments.view',
            'payments.create',
            'payments.update',
            'payments.delete',
            'payments.verify',
            'payments.refund',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'api');
        }

        /*
        |--------------------------------------------------------------------------
        | Central Roles
        |--------------------------------------------------------------------------
        */

        $superAdmin = Role::findOrCreate('super_admin', 'api');

        $admin = Role::findOrCreate('admin', 'api');

        $support = Role::findOrCreate('support', 'api');

        $billing = Role::findOrCreate('billing', 'api');

        /*
        |--------------------------------------------------------------------------
        | Super Admin
        |--------------------------------------------------------------------------
        |
        | Super admin gets all Central permissions.
        |
        */

        $superAdmin->syncPermissions(
            Permission::where('guard_name', 'api')->get()
        );

        /*
        |--------------------------------------------------------------------------
        | Admin
        |--------------------------------------------------------------------------
        */
        $admin->syncPermissions([
            // Dashboard
            'dashboard.view',

            // Users
            'users.view',
            'users.create',
            'users.update',

            // Tenants
            'tenants.view',
            'tenants.create',
            'tenants.update',

            // Subscription Plans
            'subscription_plans.view',
            'subscription_plans.create',
            'subscription_plans.update',

            // Subscriptions
            'subscriptions.view',
            'subscriptions.create',
            'subscriptions.update',
            'subscriptions.cancel',

            // Subscription Payments
            'subscription_payments.view',
            'subscription_payments.create',
            'subscription_payments.update',

            // Invoices
            'invoices.view',
            'invoices.create',
            'invoices.update',

            // Payments
            'payments.view',
            'payments.create',
            'payments.update',
            'payments.verify',
            'payments.refund',

            // Tickets
            'tickets.view',
            'tickets.create',
            'tickets.reply',
            'tickets.assign',
            'tickets.update',
            'tickets.close',

            // Notifications
            'notifications.view',
            'notifications.send',

            // Settings
            'settings.view',
            'settings.update',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Support
        |--------------------------------------------------------------------------
        |
        | Support staff mainly handles tenant support tickets.
        |
        */

        $support->syncPermissions([
            'dashboard.view',

            'tenants.view',

            'tickets.view',
            'tickets.create',
            'tickets.reply',
            'tickets.assign',
            'tickets.update',
            'tickets.close',

            'notifications.view',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Billing
        |--------------------------------------------------------------------------
        */

        $billing->syncPermissions([
            'dashboard.view',

            'tenants.view',

            'subscription_plans.view',

            'subscriptions.view',
            'subscriptions.create',
            'subscriptions.update',

            'subscription_payments.view',
            'subscription_payments.create',
            'subscription_payments.update',

            'notifications.view',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Clear Permission Cache
        |--------------------------------------------------------------------------
        */

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
