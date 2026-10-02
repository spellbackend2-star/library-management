<?php

namespace App\Http\Controllers\v1\Central;

use App\Http\Controllers\Controller;
use App\Http\Requests\Central\CentralLoginRequest;
use App\Http\Resources\v1\Central\CentralLoginResource;
use App\Services\CentralAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Psr\Http\Message\ServerRequestInterface;

class CentralAuthController extends Controller
{
    public function __construct(
        protected CentralAuthService $centralAuthService
    ) {}

    public function login(CentralLoginRequest $request, ServerRequestInterface $serverRequest): JsonResponse
    {
        $credentials = $request->validated();

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

        return response()->json(
            (new CentralLoginResource($result))->resolve($request)
        );
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user) {
            $token = $user->currentAccessToken();

            if ($token && $token->revoke()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Logged out successfully.',
                ]);
            }

            $user->tokens()
                ->where('revoked', false)
                ->get()
                ->each(fn($token) => $token->revoke());
        }

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }
}
