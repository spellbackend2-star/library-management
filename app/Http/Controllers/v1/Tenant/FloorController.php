<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Floor\StoreFloorRequest;
use App\Http\Requests\Floor\UpdateFloorRequest;
use App\Http\Resources\FloorResource;
use App\Services\FloorService;
use App\Models\Floor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FloorController extends Controller
{
    public function __construct(
        protected FloorService $floorService
    ) {}

    public function index(Request $request)
    {
        $filters = $request->only(['per_page']);
        $result = $this->floorService->getAll($filters);

        return FloorResource::collection($result['data'])
            ->additional(['meta' => $result['meta']]);
    }

    public function store(StoreFloorRequest $request): FloorResource
    {
        $floor = $this->floorService->create(
            $request->validated()
        );

        return new FloorResource($floor);
    }

    public function show(Request $request, int $floor)
    {
        $filters = $request->only(['per_page']);
        $filters['id'] = $floor;

        $result = $this->floorService->getAll($filters);

        abort_if(empty($result['data']), 404, 'Floor not found.');

        return FloorResource::collection($result['data'])
            ->additional(['meta' => $result['meta']]);
    }

    public function update(
        UpdateFloorRequest $request,
        Floor $floor
    ): FloorResource {
        $floorData = $this->floorService->update(
            $floor->id,
            $request->validated()
        );

        return new FloorResource($floorData);
    }

    public function destroy(Floor $floor): JsonResponse
    {
        $this->floorService->delete($floor->id);

        return response()->json([
            'message' => 'Floor deleted successfully.',
        ]);
    }
}
