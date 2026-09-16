<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seat\StoreSeatRequest;
use App\Http\Requests\Seat\UpdateSeatRequest;
use App\Http\Resources\SeatResource;
use App\Services\SeatService;
use App\Traits\ResponseMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SeatController extends Controller
{
    use ResponseMessage;

    public function __construct(
        protected SeatService $seatService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'room_id',
            'category_id',
            'status',
            'has_power_outlet',
            'is_accessible',
            'search',
            'sort_by',
            'sort_order',
            'per_page',
        ]);

        $result = $this->seatService->getAll($filters);

        return $this->successResponse(
            SeatResource::collection($result['data']),
            'Seats retrieved successfully.',
            200,
            $result['meta']
        );
    }

    public function store(StoreSeatRequest $request): JsonResponse
    {
        $seat = $this->seatService->create(
            $request->validated()
        );

        return $this->successResponse(
            new SeatResource($seat),
            'Seat created successfully.',
            201
        );
    }

    public function show(int $seat): JsonResponse
    {
        $seatData = $this->seatService->getById($seat);

        abort_if(!$seatData, 404, 'Seat not found.');

        return $this->successResponse(
            new SeatResource($seatData),
            'Seat retrieved successfully.'
        );
    }

    public function update(
        UpdateSeatRequest $request,
        int $seat
    ): JsonResponse {
        $seatData = $this->seatService->update(
            $seat,
            $request->validated()
        );

        return $this->successResponse(
            new SeatResource($seatData),
            'Seat updated successfully.'
        );
    }

    public function destroy(int $seat): JsonResponse
    {
        $this->seatService->delete($seat);

        return $this->successResponse(
            null,
            'Seat deleted successfully.'
        );
    }
}
