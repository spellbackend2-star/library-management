<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Services\CentralAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Psr\Http\Message\ServerRequestInterface;

class CentralAuthController extends Controller
{
    public function __construct(
        protected CentralAuthService $centralAuthService
    ) {}

    public function login(Request $request, ServerRequestInterface $serverRequest): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:1'],
        ]);

        try {
            $result = $this->centralAuthService->login(
                $credentials['email'],
                $credentials['password'],
                $serverRequest
            );
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 401);
        }

        $tenant = $result['tenant'];
        $user = $result['user'];
        $domain = $tenant?->domains()->first();

        $payload = [
            'success' => true,
            'message' => 'Login successful',
            'token' => $result['token'],
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ];

        if ($tenant) {
            $payload['tenant'] = [
                'id' => $tenant->id,
                'company_name' => $tenant->company_name,
                'tenant_code' => $tenant->tenant_code,
                'domain' => $domain?->domain,
            ];
        }

        return response()->json($payload);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $tenant = $this->centralAuthService->getTenantForUser($user);
        $domain = $tenant?->domains()->first();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'tenant' => $tenant ? [
                'id' => $tenant->id,
                'company_name' => $tenant->company_name,
                'tenant_code' => $tenant->tenant_code,
                'domain' => $domain?->domain,
            ] : null,
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'owner' => ['required', 'string', 'max:255'],
            'company_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'subdomain' => ['required', 'string', 'max:255'],
        ]);

        try {
            $result = $this->centralAuthService->registerTenant($data);
        } catch (\RuntimeException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Tenant registered successfully',
            'tenant' => $result['tenant'],
            'domain' => $result['domain'],
        ], 201);
    }
}
