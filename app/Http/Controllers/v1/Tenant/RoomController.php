<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Room\StoreRoomRequest;
use App\Http\Requests\Room\UpdateRoomRequest;
use App\Http\Resources\RoomResource;
use App\Services\RoomService;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function __construct(
        protected RoomService $roomService
    ) {}

    public function index(Request $request)
    {
        $filters = $request->only(['per_page']);
        $result = $this->roomService->getAll($filters);

        return RoomResource::collection($result['data'])
            ->additional(['meta' => $result['meta']]);
    }

    public function store(StoreRoomRequest $request): RoomResource
    {
        $room = $this->roomService->create(
            $request->validated()
        );

        return new RoomResource($room);
    }

    public function show(Request $request, int $room)
    {
        $filters = $request->only(['per_page']);
        $filters['id'] = $room;

        $result = $this->roomService->getAll($filters);

        abort_if(empty($result['data']), 404, 'Room not found.');

        return RoomResource::collection($result['data'])
            ->additional(['meta' => $result['meta']]);
    }

    public function update(
        UpdateRoomRequest $request,
        Room $room
    ): RoomResource {
        $roomData = $this->roomService->update(
            $room->id,
            $request->validated()
        );

        return new RoomResource($roomData);
    }

    public function destroy(Room $room): JsonResponse
    {
        $this->roomService->delete($room->id);

        return response()->json([
            'message' => 'Room deleted successfully.',
        ]);
    }
}
