<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Floor\StoreFloorRequest;
use App\Http\Requests\Floor\UpdateFloorRequest;
use App\Http\Resources\FloorResource;
use App\Services\FloorService;
use App\Models\Floor;
use App\Traits\ResponseMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FloorController extends Controller
{
    use ResponseMessage;

    public function __construct(
        protected FloorService $floorService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'sort_by',
            'sort_order',
            'per_page',
        ]);

        $result = $this->floorService->getAll($filters);

        return $this->successResponse(
            FloorResource::collection($result['data']),
            'Floors retrieved successfully.',
            200,
            $result['meta']
        );
    }

    public function store(StoreFloorRequest $request): JsonResponse
    {
        $floor = $this->floorService->create(
            $request->validated()
        );

        return $this->successResponse(
            new FloorResource($floor),
            'Floor created successfully.',
            201
        );
    }

    public function show(int $floor): JsonResponse
    {
        $floorData = $this->floorService->getById($floor);

        abort_if(!$floorData, 404, 'Floor not found.');

        return $this->successResponse(
            new FloorResource($floorData),
            'Floor retrieved successfully.'
        );
    }

    public function update(
        UpdateFloorRequest $request,
        Floor $floor
    ): JsonResponse {
        $floorData = $this->floorService->update(
            $floor->id,
            $request->validated()
        );

        return $this->successResponse(
            new FloorResource($floorData),
            'Floor updated successfully.'
        );
    }

    public function destroy(Floor $floor): JsonResponse
    {
        $this->floorService->delete($floor->id);

        return $this->successResponse(
            null,
            'Floor deleted successfully.'
        );
    }
}
