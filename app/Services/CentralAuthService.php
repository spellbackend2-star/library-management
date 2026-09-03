<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use App\Repositories\Interface\TenantInterface;
use Database\Seeders\Tenant\RolePermissionSeeder;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Http\Controllers\AccessTokenController;
use Psr\Http\Message\ServerRequestInterface;
use Spatie\Permission\Models\Role;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentified;

class CentralAuthService
{
    public function __construct(
        protected TenantInterface $tenantRepository
    ) {}

    /**
     * Authenticate central admin only.
     */
    public function login(
        string $email,
        string $password,
        ServerRequestInterface $serverRequest
    ): array {
        $centralUser = User::where('email', $email)->first();

        if (
            !$centralUser ||
            !Hash::check($password, $centralUser->password)
        ) {
            throw new \RuntimeException('Invalid credentials.');
        }

        $token = $this->issueCentralToken(
            $email,
            $password,
            $serverRequest
        );

        return [
            'token' => $token,
            'tenant' => null,
            'user' => $centralUser,
        ];
    }

    /**
     * Issue Passport token for central admin.
     */
    protected function issueCentralToken(
        string $email,
        string $password,
        ServerRequestInterface $serverRequest
    ): array {
        $clientId = config('passport.central_client_id');
        $clientSecret = config('passport.central_client_secret');

        if (!$clientId || !$clientSecret) {
            throw new \RuntimeException(
                'Central Passport client credentials are not configured.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Verify central Passport client
        |--------------------------------------------------------------------------
        */

        $client = DB::table('oauth_clients')
            ->where('id', $clientId)
            ->where('revoked', false)
            ->first();

        if (!$client) {
            throw new \RuntimeException(
                'Central Passport client not found.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Issue token
        |--------------------------------------------------------------------------
        */

        $tokenRequest = $serverRequest->withParsedBody([
            'grant_type' => 'password',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'username' => $email,
            'password' => $password,
            'scope' => '*',
        ]);

        $passportResponse = app(AccessTokenController::class)
            ->issueToken(
                $tokenRequest,
                new Response()
            );

        $token = json_decode(
            (string) $passportResponse->getContent(),
            true
        );

        if (!is_array($token) || isset($token['error'])) {
            throw new \RuntimeException(
                $token['error_description']
                    ?? $token['message']
                    ?? 'Unable to issue central access token.'
            );
        }

        return $token;
    }

    /**
     * Get tenant by authenticated user's email.
     */
    public function getTenantForUser(User $user): ?Tenant
    {
        return $this->tenantRepository->findByOwnerEmail(
            $user->email
        );
    }

    /**
     * Register a new tenant.
     *
     * Flow:
     *   1. Create central tenant row + domain.
     *   2. Initialize tenancy to provision the tenant DB and run migrations.
     *   3. Inside the tenant context: create the owner, seed
     *      RolePermissionSeeder, assign the admin role, create the
     *      per-tenant Passport client, persist client credentials
     *      on the central tenant row.
     *
     * @return array{tenant: Tenant, domain: string}
     */
    public function registerTenant(array $data): array
    {
        $tenant = Tenant::create([
            'company_name' => $data['company_name'],
            'tenant_code' => $data['subdomain'],
            'owner_email' => $data['email'],
        ]);

        $domain = $tenant->domains()->create([
            'domain' => $data['subdomain']
                . '.'
                . config('tenancy.central_domains')[0],
        ]);

        try {
            $tenant->run(function () use ($data, $tenant) {
                $owner = User::create([
                    'name' => $data['owner'],
                    'email' => $data['email'],
                    'password' => bcrypt($data['password']),
                ]);

                Artisan::call('db:seed', [
                    '--class' => RolePermissionSeeder::class,
                    '--force' => true,
                ]);

                $adminRole = Role::where('name', 'admin')
                    ->where('guard_name', 'api')
                    ->first();

                if ($adminRole) {
                    $owner->assignRole($adminRole->name);
                }

                $client = app(ClientRepository::class)
                    ->createPasswordGrantClient(
                        name: $data['company_name']
                            . ' Password Grant Client',
                        provider: 'users',
                        confidential: true,
                    );

                $tenant->update([
                    'passport_client_id' => $client->id,
                    'passport_client_secret' => $client->plainSecret,
                ]);
            });
        } catch (\Throwable $e) {
            throw new \RuntimeException(
                'Failed to create tenant: ' . $e->getMessage()
            );
        }

        return [
            'tenant' => $tenant->fresh(),
            'domain' => $domain->domain,
        ];
    }
}