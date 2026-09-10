<?php

namespace Database\Seeders\Tenant;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsTableSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            [
                'group' => 'auth',
                'key' => 'default_staff_role',
                'value' => 'manager',
                'type' => 'string',
                'description' => 'Default role assigned to newly created staff.',
            ],
            [
                'group' => 'auth',
                'key' => 'default_user_role',
                'value' => 'member',
                'type' => 'string',
                'description' => 'Default role assigned to newly registered users/members.',
            ],
            [
                'group' => 'auth',
                'key' => 'user_roles_permissions',
                'value' => [
                    'admin' => ['staff.view', 'staff.create', 'staff.update', 'staff.delete', 'staff.assign-role'],
                    'manager' => ['staff.view', 'staff.create', 'staff.update'],
                    'librarian' => ['staff.view'],
                    'clerk' => ['staff.view'],
                ],
                'type' => 'json',
                'description' => 'Mapping of user roles to their permissions.',
            ],
            [
                'group' => 'general',
                'key' => 'library_name',
                'value' => 'Library',
                'type' => 'string',
                'description' => 'Display name of the library.',
            ],
            [
                'group' => 'general',
                'key' => 'default_email',
                'value' => 'info@library.test',
                'type' => 'string',
                'description' => 'Default contact email for the library.',
            ],
            [
                'group' => 'general',
                'key' => 'currency',
                'value' => 'NPR',
                'type' => 'string',
                'description' => 'Default currency code for billing.',
            ],
            [
                'group' => 'payment',
                'key' => 'enabled_methods',
                'value' => ['CASH', 'KHALTI', 'ESEWA'],
                'type' => 'json',
                'description' => 'Payment methods enabled for the library.',
            ],
            [
                'group' => 'payment',
                'key' => 'khalti_enabled',
                'value' => true,
                'type' => 'boolean',
                'description' => 'Enable Khalti as a payment gateway.',
            ],
            [
                'group' => 'payment',
                'key' => 'esewa_enabled',
                'value' => true,
                'type' => 'boolean',
                'description' => 'Enable eSewa as a payment gateway.',
            ],
            [
                'group' => 'booking',
                'key' => 'advance_booking_days',
                'value' => 7,
                'type' => 'integer',
                'description' => 'Maximum days in advance a member can book a seat.',
            ],
            [
                'group' => 'booking',
                'key' => 'allow_loyalty_points',
                'value' => true,
                'type' => 'boolean',
                'description' => 'Allow loyalty points as a payment method.',
            ],
        ];

        foreach ($defaults as $setting) {
            Setting::updateOrCreate(
                ['group' => $setting['group'], 'key' => $setting['key']],
                $setting
            );
        }
    }
}
