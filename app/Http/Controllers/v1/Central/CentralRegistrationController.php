<?php

namespace App\Http\Controllers\v1\Central;

use App\Http\Controllers\Controller;
use App\Http\Requests\Central\CentralRegisterRequest;
use App\Http\Resources\v1\Central\CentralRegistrationResource;
use App\Services\Central\CentralTenantService;
use App\Traits\ResponseMessage;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;

class CentralRegistrationController extends Controller
{
    use ResponseMessage;

    public function __construct(
        protected CentralTenantService $centralTenantService
    ) {}

    public function register(CentralRegisterRequest $request): JsonResponse
    {
        try {
            $result = $this->centralTenantService->registerTenant(
                $request->validated()
            );

            return $this->successResponse(
                new CentralRegistrationResource($result),
                'Tenant registered successfully.',
                201
            );
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return $this->errorResponse(
                    'This subdomain is already taken. Please choose another one.',
                    422
                );
            }

            throw $e;
        } catch (\RuntimeException $e) {
            return $this->errorResponse(
                $e->getMessage(),
                422
            );
        }
    }
}
