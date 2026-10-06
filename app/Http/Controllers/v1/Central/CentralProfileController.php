<?php

namespace App\Http\Controllers\v1\Central;

use App\Http\Controllers\Controller;
use App\Http\Requests\Central\CentralProfileUpdateRequest;
use App\Http\Resources\v1\Central\CentralProfileResource;
use App\Services\Central\CentralAuthService;
use App\Traits\ResponseMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CentralProfileController extends Controller
{
    use ResponseMessage;

    public function __construct(
        protected CentralAuthService $centralAuthService
    ) {}

    /**
     * Get the authenticated central user profile.
     */
    public function me(Request $request): JsonResponse
    {
        return $this->profileResponse($request);
    }

    /**
     * Get the authenticated central user profile.
     */
    public function profile(Request $request): JsonResponse
    {
        return $this->profileResponse($request);
    }

    /**
     * Update the authenticated central user profile.
     */
    public function update(CentralProfileUpdateRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return $this->errorResponse(
                'Unauthenticated.',
                401
            );
        }

        $user->update($request->validated());

        return $this->successResponse(
            new CentralProfileResource([
                'user' => $user->fresh(),
                'updated' => true,
            ]),
            'Profile updated successfully.'
        );
    }

    /**
     * Build the authenticated profile response.
     */
    protected function profileResponse(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return $this->errorResponse(
                'Unauthenticated.',
                401
            );
        }

        $tenant = $this->centralAuthService->getTenantForUser($user);

        return $this->successResponse(
            new CentralProfileResource([
                'user' => $user,
                'tenant' => $tenant,
            ]),
            'Profile retrieved successfully.'
        );
    }
}
