<?php

namespace App\Services\Central;

use App\Models\Tenant;
use App\Models\User;
use App\Repositories\Interface\TenantInterface;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Http\Controllers\AccessTokenController;
use Psr\Http\Message\ServerRequestInterface;

class CentralAuthService
{
    public function __construct(protected TenantInterface $tenantRepository) {}

    public function login(
        string $email,
        string $password,
        ServerRequestInterface $serverRequest
    ): array {
        $centralUser = User::where('email', $email)->first();

        if (
            ! $centralUser ||
            ! Hash::check($password, $centralUser->password)
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

    protected function issueCentralToken(
        string $email,
        string $password,
        ServerRequestInterface $serverRequest
    ): array {
        $clientId = config('passport.central_client_id');
        $clientSecret = config('passport.central_client_secret');

        if (! $clientId || ! $clientSecret) {
            throw new \RuntimeException(
                'Central Passport client credentials are not configured.'
            );
        }

        $client = DB::table('oauth_clients')
            ->where('id', $clientId)
            ->where('revoked', false)
            ->first();

        if (! $client) {
            throw new \RuntimeException(
                'Central Passport client not found.'
            );
        }

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
                new Response
            );

        $token = json_decode(
            (string) $passportResponse->getContent(),
            true
        );

        if (! is_array($token) || isset($token['error'])) {
            throw new \RuntimeException(
                $token['error_description']
                    ?? $token['message']
                    ?? 'Unable to issue central access token.'
            );
        }

        return $token;
    }

    public function getTenantForUser(User $user): ?Tenant
    {
        return $this->tenantRepository->findByOwnerEmail(
            $user->email
        );
    }
}
