<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use App\Repositories\Interface\TenantInterface;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Http\Controllers\AccessTokenController;
use Psr\Http\Message\ServerRequestInterface;
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
     * Authenticate a tenant user using tenant domain.
     */
    public function loginByDomain(
        string $domain,
        string $email,
        string $password,
        ServerRequestInterface $serverRequest
    ): array {
        $tenant = $this->tenantRepository->findByDomain($domain);

        if (!$tenant) {
            throw new \RuntimeException('Invalid credentials.');
        }

        if (
            !$tenant->passport_client_id ||
            !$tenant->passport_client_secret
        ) {
            throw new \RuntimeException(
                'Tenant Passport client is not configured.'
            );
        }

        $user = null;
        $token = null;

        try {
            /*
            |--------------------------------------------------------------------------
            | Initialize tenant
            |--------------------------------------------------------------------------
            */

            tenancy()->initialize($tenant);

            /*
            |--------------------------------------------------------------------------
            | Find tenant user
            |--------------------------------------------------------------------------
            */

            $user = User::where('email', $email)->first();

            if (
                !$user ||
                !Hash::check($password, $user->password)
            ) {
                throw new \RuntimeException(
                    'Invalid credentials.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Issue tenant Passport token
            |--------------------------------------------------------------------------
            */

            $tokenRequest = $serverRequest->withParsedBody([
                'grant_type' => 'password',
                'client_id' => $tenant->passport_client_id,
                'client_secret' => $tenant->passport_client_secret,
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
        } catch (TenantCouldNotBeIdentified $e) {
            throw new \RuntimeException(
                'Invalid credentials.'
            );
        } finally {
            /*
            |--------------------------------------------------------------------------
            | Always end tenancy
            |--------------------------------------------------------------------------
            */

            if (tenancy()->initialized) {
                tenancy()->end();
            }
        }

        if (!is_array($token) || isset($token['error'])) {
            throw new \RuntimeException(
                $token['error_description']
                    ?? $token['message']
                    ?? 'Unable to issue tenant access token.'
            );
        }

        return [
            'token' => $token,
            'tenant' => $tenant,
            'user' => $user,
        ];
    }
}