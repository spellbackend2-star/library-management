<?php

namespace Database\Seeders\Central;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CentralUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('CENTRAL_ADMIN_EMAIL', 'admin@library.test');
        $password = env('CENTRAL_ADMIN_PASSWORD', 'password');

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Central Admin',
                'password' => Hash::make($password),
            ]
        );

        // The configured Central Admin account is the system administrator.
        // Ensure it has the role created by CentralRolePermissionSeeder.
        $user->assignRole('super_admin');

        $this->command?->info("Central admin ready: {$email} / {$password}");
    }
}
