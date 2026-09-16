<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Room\StoreRoomRequest;
use App\Http\Requests\Room\UpdateRoomRequest;
use App\Http\Resources\RoomResource;
use App\Services\RoomService;
use App\Models\Room;
use App\Traits\ResponseMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    use ResponseMessage;

    public function __construct(
        protected RoomService $roomService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'sort_by',
            'sort_order',
            'per_page',
        ]);

        $result = $this->roomService->getAll($filters);

        return $this->successResponse(
            RoomResource::collection($result['data']),
            'Rooms retrieved successfully.',
            200,
            $result['meta']
        );
    }

    public function store(StoreRoomRequest $request): JsonResponse
    {
        $room = $this->roomService->create(
            $request->validated()
        );

        return $this->successResponse(
            new RoomResource($room),
            'Room created successfully.',
            201
        );
    }

    public function show(int $room): JsonResponse
    {
        $roomData = $this->roomService->getById($room);

        abort_if(!$roomData, 404, 'Room not found.');

        return $this->successResponse(
            new RoomResource($roomData),
            'Room retrieved successfully.'
        );
    }

    public function update(
        UpdateRoomRequest $request,
        Room $room
    ): JsonResponse {
        $roomData = $this->roomService->update(
            $room->id,
            $request->validated()
        );

        return $this->successResponse(
            new RoomResource($roomData),
            'Room updated successfully.'
        );
    }

    public function destroy(Room $room): JsonResponse
    {
        $this->roomService->delete($room->id);

        return $this->successResponse(
            null,
            'Room deleted successfully.'
        );
    }
}
