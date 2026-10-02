<?php

namespace App\Http\Controllers\v1\Central;

use App\Http\Controllers\Controller;
use App\Http\Requests\Central\CentralProfileUpdateRequest;
use App\Http\Resources\v1\Central\CentralProfileResource;
use App\Services\CentralAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CentralProfileController extends Controller
{
    public function __construct(
        protected CentralAuthService $centralAuthService
    ) {}

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $tenant = $this->centralAuthService->getTenantForUser($user);

        return response()->json(
            (new CentralProfileResource([
                'user' => $user,
                'tenant' => $tenant,
            ]))->resolve($request)
        );
    }

    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $tenant = $this->centralAuthService->getTenantForUser($user);

        return response()->json(
            (new CentralProfileResource([
                'user' => $user,
                'tenant' => $tenant,
            ]))->resolve($request)
        );
    }

    public function update(CentralProfileUpdateRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user->update($request->validated());

        return response()->json(
            (new CentralProfileResource([
                'user' => $user,
                'updated' => true,
            ]))->resolve($request)
        );
    }
}