<?php

namespace Database\Seeders\Tenant;

use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class SettingsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $settings = [
            [
                'group' => 'general',
                'key' => 'logo',
                'value' => null,
                'type' => 'string',
                'description' => 'Library logo.',
                'is_locked' => false,
            ],
            [
                'group' => 'general',
                'key' => 'favicon',
                'value' => null,
                'type' => 'string',
                'description' => 'Library favicon.',
                'is_locked' => false,
            ],
            [
                'group' => 'general',
                'key' => 'company_name',
                'value' => 'Library',
                'type' => 'string',
                'description' => 'Company or library name.',
                'is_locked' => false,
            ],
            [
                'group' => 'general',
                'key' => 'email',
                'value' => 'info@library.test',
                'type' => 'string',
                'description' => 'Default contact email.',
                'is_locked' => false,
            ],
            [
                'group' => 'general',
                'key' => 'address',
                'value' => '',
                'type' => 'string',
                'description' => 'Company or library address.',
                'is_locked' => false,
            ],
            [
                'group' => 'general',
                'key' => 'phone',
                'value' => '',
                'type' => 'string',
                'description' => 'Company or library phone number.',
                'is_locked' => false,
            ],
            [
                'group' => 'general',
                'key' => 'date_format',
                'value' => 'Y-m-d',
                'type' => 'string',
                'description' => 'Default date format.',
                'is_locked' => false,
            ],
            [
                'group' => 'general',
                'key' => 'currency',
                'value' => 'NPR',
                'type' => 'string',
                'description' => 'Default currency.',
                'is_locked' => false,
            ],
            [
                'group' => 'company',
                'key' => 'company_name',
                'value' => 'Library',
                'type' => 'string',
                'description' => 'Company or library name.',
                'is_locked' => false,
            ],
            [
                'group' => 'company',
                'key' => 'address',
                'value' => null,
                'type' => 'string',
                'description' => 'Company address.',
                'is_locked' => false,
            ],
            [
                'group' => 'company',
                'key' => 'phone',
                'value' => null,
                'type' => 'string',
                'description' => 'Company phone number.',
                'is_locked' => false,
            ],
            [
                'group' => 'company',
                'key' => 'email',
                'value' => null,
                'type' => 'string',
                'description' => 'Company email address.',
                'is_locked' => false,
            ],
            [
                'group' => 'company',
                'key' => 'website',
                'value' => null,
                'type' => 'string',
                'description' => 'Company website.',
                'is_locked' => false,
            ],
            [
                'group' => 'company',
                'key' => 'tax_number',
                'value' => null,
                'type' => 'string',
                'description' => 'Company tax number.',
                'is_locked' => false,
            ],
            [
                'group' => 'company',
                'key' => 'vat_number',
                'value' => null,
                'type' => 'string',
                'description' => 'Company VAT number.',
                'is_locked' => false,
            ],
            [
                'group' => 'company',
                'key' => 'logo',
                'value' => null,
                'type' => 'string',
                'description' => 'Company logo.',
                'is_locked' => false,
            ],
            [
                'group' => 'company',
                'key' => 'favicon',
                'value' => null,
                'type' => 'string',
                'description' => 'Company favicon.',
                'is_locked' => false,
            ],
            [
                'group' => 'appearance',
                'key' => 'logo',
                'value' => null,
                'type' => 'string',
                'description' => 'Tenant logo path.',
                'is_locked' => false,
            ],
            [
                'group' => 'appearance',
                'key' => 'favicon',
                'value' => null,
                'type' => 'string',
                'description' => 'Tenant favicon path.',
                'is_locked' => false,
            ],
            [
                'group' => 'appearance',
                'key' => 'theme',
                'value' => 'light',
                'type' => 'string',
                'description' => 'Appearance theme.',
                'is_locked' => false,
            ],
            [
                'group' => 'appearance',
                'key' => 'primary_color',
                'value' => '#2563EB',
                'type' => 'string',
                'description' => 'Primary brand color.',
                'is_locked' => false,
            ],
            [
                'group' => 'appearance',
                'key' => 'secondary_color',
                'value' => '#64748B',
                'type' => 'string',
                'description' => 'Secondary brand color.',
                'is_locked' => false,
            ],
            [
                'group' => 'invoice',
                'key' => 'invoice_prefix',
                'value' => 'INV',
                'type' => 'string',
                'description' => 'Invoice prefix.',
                'is_locked' => false,
            ],
            [
                'group' => 'invoice',
                'key' => 'invoice_start_number',
                'value' => '1000',
                'type' => 'string',
                'description' => 'Invoice starting number.',
                'is_locked' => false,
            ],
            [
                'group' => 'invoice',
                'key' => 'invoice_number_format',
                'value' => '{PREFIX}-{YEAR}-{NUMBER}',
                'type' => 'string',
                'description' => 'Invoice number format.',
                'is_locked' => false,
            ],
            [
                'group' => 'invoice',
                'key' => 'invoice_footer',
                'value' => null,
                'type' => 'string',
                'description' => 'Invoice footer text.',
                'is_locked' => false,
            ],
            [
                'group' => 'invoice',
                'key' => 'invoice_notes',
                'value' => null,
                'type' => 'string',
                'description' => 'Default invoice notes.',
                'is_locked' => false,
            ],
            [
                'group' => 'invoice',
                'key' => 'invoice_currency',
                'value' => 'NPR',
                'type' => 'string',
                'description' => 'Invoice currency.',
                'is_locked' => false,
            ],
            [
                'group' => 'invoice',
                'key' => 'invoice_tax_enabled',
                'value' => '0',
                'type' => 'string',
                'description' => 'Whether tax is enabled.',
                'is_locked' => false,
            ],
            [
                'group' => 'invoice',
                'key' => 'invoice_tax_percentage',
                'value' => '0',
                'type' => 'string',
                'description' => 'Default invoice tax percentage.',
                'is_locked' => false,
            ],
            [
                'group' => 'invoice',
                'key' => 'invoice_due_days',
                'value' => '7',
                'type' => 'string',
                'description' => 'Default due days for invoices.',
                'is_locked' => false,
            ],
            [
                'group' => 'notification',
                'key' => 'email_notification_enabled',
                'value' => '1',
                'type' => 'boolean',
                'description' => 'Enable email notifications.',
                'is_locked' => false,
            ],
            [
                'group' => 'notification',
                'key' => 'sms_notification_enabled',
                'value' => '0',
                'type' => 'boolean',
                'description' => 'Enable SMS notifications.',
                'is_locked' => false,
            ],
            [
                'group' => 'notification',
                'key' => 'invoice_notification_enabled',
                'value' => '1',
                'type' => 'boolean',
                'description' => 'Enable invoice notifications.',
                'is_locked' => false,
            ],
            [
                'group' => 'notification',
                'key' => 'payment_notification_enabled',
                'value' => '1',
                'type' => 'boolean',
                'description' => 'Enable payment notifications.',
                'is_locked' => false,
            ],
            [
                'group' => 'notification',
                'key' => 'booking_notification_enabled',
                'value' => '1',
                'type' => 'boolean',
                'description' => 'Enable booking notifications.',
                'is_locked' => false,
            ],
            [
                'group' => 'notification',
                'key' => 'overdue_notification_enabled',
                'value' => '1',
                'type' => 'boolean',
                'description' => 'Enable overdue notifications.',
                'is_locked' => false,
            ],
            [
                'group' => 'notification',
                'key' => 'fine_notification_enabled',
                'value' => '1',
                'type' => 'boolean',
                'description' => 'Enable fine notifications.',
                'is_locked' => false,
            ],
            [
                'group' => 'notification',
                'key' => 'membership_expiry_notification_enabled',
                'value' => '1',
                'type' => 'boolean',
                'description' => 'Enable membership expiry notifications.',
                'is_locked' => false,
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                [
                    'group' => $setting['group'],
                    'key' => $setting['key'],
                ],
                $setting
            );
        }
    }
}
