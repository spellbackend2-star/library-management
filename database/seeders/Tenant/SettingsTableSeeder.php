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
