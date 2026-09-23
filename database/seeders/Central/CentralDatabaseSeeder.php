<?php

namespace Database\Seeders\Central;

use Database\Seeders\CentralRolePermissionSeeder;
use Illuminate\Database\Seeder;

class CentralDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CentralUserSeeder::class,
            CentralPassportSeeder::class,
            CentralRolePermissionSeeder::class,
        ]);
    }
}
