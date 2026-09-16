<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Locker\StoreLockerRequest;
use App\Http\Requests\Locker\UpdateLockerRequest;
use App\Http\Resources\LockerResource;
use App\Services\LockerService;
use App\Traits\ResponseMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LockerController extends Controller
{
    use ResponseMessage;

    public function __construct(
        protected LockerService $lockerService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'floor_id',
            'locker_type',
            'status',
            'search',
            'sort_by',
            'sort_order',
            'per_page',
        ]);

        $result = $this->lockerService->getAll($filters);

        return $this->successResponse(
            LockerResource::collection($result['data']),
            'Lockers retrieved successfully.',
            200,
            $result['meta']
        );
    }

    public function store(StoreLockerRequest $request): JsonResponse
    {
        $locker = $this->lockerService->create(
            $request->validated()
        );

        return $this->successResponse(
            new LockerResource($locker),
            'Locker created successfully.',
            201
        );
    }

    public function show(int $locker): JsonResponse
    {
        $lockerData = $this->lockerService->getById($locker);

        abort_if(!$lockerData, 404, 'Locker not found.');

        return $this->successResponse(
            new LockerResource($lockerData),
            'Locker retrieved successfully.'
        );
    }

    public function update(
        UpdateLockerRequest $request,
        int $locker
    ): JsonResponse {
        $lockerData = $this->lockerService->update(
            $locker,
            $request->validated()
        );

        return $this->successResponse(
            new LockerResource($lockerData),
            'Locker updated successfully.'
        );
    }

    public function destroy(int $locker): JsonResponse
    {
        $this->lockerService->delete($locker);

        return $this->successResponse(
            null,
            'Locker deleted successfully.'
        );
    }
}
