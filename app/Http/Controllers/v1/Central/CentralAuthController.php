<?php

namespace App\Http\Controllers\v1\Central;

use App\Http\Controllers\Controller;
use App\Http\Requests\Central\CentralLoginRequest;
use App\Http\Resources\v1\Central\CentralLoginResource;
use App\Services\Central\CentralAuthService;
use App\Traits\ResponseMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Psr\Http\Message\ServerRequestInterface;

class CentralAuthController extends Controller
{
    use ResponseMessage;

    public function __construct(
        protected CentralAuthService $centralAuthService
    ) {}

    public function login(
        CentralLoginRequest $request,
        ServerRequestInterface $serverRequest
    ): JsonResponse {
        try {
            $credentials = $request->validated();

            $result = $this->centralAuthService->login(
                $credentials['email'],
                $credentials['password'],
                $serverRequest
            );

            return $this->successResponse(
                new CentralLoginResource($result),
                'Login successful.'
            );

        } catch (\RuntimeException $e) {
            return $this->errorResponse(
                $e->getMessage(),
                401
            );
        }
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user) {
            $token = $user->currentAccessToken();

            if ($token && $token->revoke()) {
                return $this->successResponse(
                    null,
                    'Logged out successfully.'
                );
            }

            $user->tokens()
                ->where('revoked', false)
                ->get()
                ->each(fn ($token) => $token->revoke());
        }

        return $this->successResponse(
            null,
            'Logged out successfully.'
        );
    }
}