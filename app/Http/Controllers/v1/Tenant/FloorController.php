<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Floor\StoreFloorRequest;
use App\Http\Requests\Floor\UpdateFloorRequest;
use App\Http\Resources\FloorResource;
use App\Services\FloorService;
use App\Models\Floor;
use Illuminate\Http\JsonResponse;

class FloorController extends Controller
{
    public function __construct(
        protected FloorService $floorService
    ) {}

    public function index()
    {
        return FloorResource::collection(
            $this->floorService->getAll()
        );
    }

    public function store(StoreFloorRequest $request): FloorResource
    {
        $floor = $this->floorService->create(
            $request->validated()
        );

        return new FloorResource($floor);
    }

    public function show(Floor $floor): FloorResource
    {
        return new FloorResource($floor);
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
