<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\ClientRepository;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    private const CENTRAL_CLIENT_NAME = 'Central Password Grant Client';

    public function run(): void
    {
        $adminEmail = env('CENTRAL_ADMIN_EMAIL', 'admin@library.test');
        $adminPassword = env('CENTRAL_ADMIN_PASSWORD', 'password');

        /*
        |--------------------------------------------------------------------------
        | Central Admin
        |--------------------------------------------------------------------------
        */

        User::updateOrCreate(
            ['email' => $adminEmail],
            [
                'name' => 'Central Admin',
                'password' => Hash::make($adminPassword),
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Central Passport Password Grant Client
        |--------------------------------------------------------------------------
        */

        $existingClient = DB::table('oauth_clients')
            ->where('name', self::CENTRAL_CLIENT_NAME)
            ->where('revoked', false)
            ->first();

        if (!$existingClient) {
            $client = app(ClientRepository::class)->createPasswordGrantClient(
                name: self::CENTRAL_CLIENT_NAME,
                provider: 'users',
                confidential: true,
            );

            $this->command?->info('');
            $this->command?->info('Central Passport client created.');
            $this->command?->info("CENTRAL_PASSPORT_CLIENT_ID={$client->id}");
            $this->command?->info("CENTRAL_PASSPORT_CLIENT_SECRET={$client->plainSecret}");
            $this->command?->info('');
            $this->command?->warn(
                'Copy CENTRAL_PASSPORT_CLIENT_ID and CENTRAL_PASSPORT_CLIENT_SECRET to your .env file.'
            );
        } else {
            $this->command?->info(
                "Central Passport client already exists: {$existingClient->id}"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Admin Information
        |--------------------------------------------------------------------------
        */

        $this->command?->info(
            "Central admin ready: {$adminEmail} / {$adminPassword}"
        );
    }
}