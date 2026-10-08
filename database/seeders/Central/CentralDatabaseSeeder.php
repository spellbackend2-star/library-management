<?php

namespace Database\Seeders\Central;

use Illuminate\Database\Seeder;

class CentralDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CentralRolePermissionSeeder::class,
            CentralUserSeeder::class,
            CentralPassportSeeder::class,
            CentralSettingsTableSeeder::class,
        ]);
    }
}
