<?php

namespace Database\Seeders\Central;

use Illuminate\Database\Seeder;

class CentralDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CentralUserSeeder::class,
            CentralPassportSeeder::class,
        ]);
    }
}
