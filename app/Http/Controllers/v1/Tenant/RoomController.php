<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Room\StoreRoomRequest;
use App\Http\Requests\Room\UpdateRoomRequest;
use App\Http\Resources\RoomResource;
use App\Services\RoomService;
use App\Models\Room;
use Illuminate\Http\JsonResponse;

class RoomController extends Controller
{
    public function __construct(
        protected RoomService $roomService
    ) {}

    public function index()
    {
        return RoomResource::collection(
            $this->roomService->getAll()
        );
    }

    public function store(StoreRoomRequest $request): RoomResource
    {
        $room = $this->roomService->create(
            $request->validated()
        );

        return new RoomResource($room);
    }

    public function show(Room $room): RoomResource
    {
        return new RoomResource($room);
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
