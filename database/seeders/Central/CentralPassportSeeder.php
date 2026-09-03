<?php

namespace Database\Seeders\Central;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\ClientRepository;

class CentralPassportSeeder extends Seeder
{
    public const CLIENT_NAME = 'Central Password Grant Client';

    public function run(): void
    {
        $existing = DB::table('oauth_clients')
            ->where('name', self::CLIENT_NAME)
            ->where('revoked', false)
            ->first();

        if ($existing) {
            $this->command?->info(
                "Central Passport client already exists: {$existing->id}"
            );
            return;
        }

        $client = app(ClientRepository::class)->createPasswordGrantClient(
            name: self::CLIENT_NAME,
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
    }
}
