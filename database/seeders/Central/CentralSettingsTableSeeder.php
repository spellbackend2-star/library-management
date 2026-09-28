<?php

namespace Database\Seeders\Central;

use App\Models\CentralSetting;
use App\Services\CentralSettingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class CentralSettingsTableSeeder extends Seeder
{
    /**
     * Seed the default central settings for every settings group.
     *
     * These rows live in the central database only. Running the seeder
     * again does not create duplicates.
     */
    public function run(): void
    {
        if (! Schema::connection(config('tenancy.database.central_connection'))
            ->hasTable('settings')) {
            $this->command?->warn('Central settings table not found, skipping.');

            return;
        }

        foreach (CentralSettingService::GROUPS as $group => $definitions) {
            foreach ($definitions as $key => $definition) {
                $value = $definition['value'];

                if ($definition['type'] === 'boolean') {
                    $value = $value ? '1' : '0';
                }

                CentralSetting::updateOrCreate(
                    [
                        'group' => $group,
                        'key' => $key,
                    ],
                    [
                        'value' => $value,
                        'type' => $definition['type'],
                        'description' => $definition['description'],
                        'is_locked' => false,
                    ]
                );
            }
        }
    }
}
