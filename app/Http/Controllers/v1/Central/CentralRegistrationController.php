<?php

namespace App\Http\Controllers\v1\Central;

use App\Http\Controllers\Controller;
use App\Http\Requests\Central\CentralRegisterRequest;
use App\Http\Resources\v1\Central\CentralRegistrationResource;
use App\Services\CentralAuthService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;

class CentralRegistrationController extends Controller
{
    public function __construct(
        protected CentralAuthService $centralAuthService
    ) {}

    public function register(CentralRegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            $result = $this->centralAuthService->registerTenant($data);
        } catch (QueryException $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry') || str_contains($e->getMessage(), '1062')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This subdomain is already taken. Please choose another one.',
                ], 422);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to register tenant. Please try again.',
            ], 500);
        } catch (\RuntimeException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }

        return response()->json(
            (new CentralRegistrationResource($result))->resolve($request),
            201
        );
    }
}