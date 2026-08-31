<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seat\StoreSeatRequest;
use App\Http\Requests\Seat\UpdateSeatRequest;
use App\Http\Resources\SeatResource;
use App\Services\SeatService;
use App\Models\Seat;
use Illuminate\Http\JsonResponse;

class SeatController extends Controller
{
    public function __construct(
        protected SeatService $seatService
    ) {}

    public function index()
    {
        return SeatResource::collection(
            $this->seatService->getAll()
        );
    }

    public function store(StoreSeatRequest $request): SeatResource
    {
        $seat = $this->seatService->create(
            $request->validated()
        );

        return new SeatResource($seat);
    }

    public function show(Seat $seat): SeatResource
    {
        return new SeatResource($seat);
    }

    public function update(
        UpdateSeatRequest $request,
        Seat $seat
    ): SeatResource {
        $seatData = $this->seatService->update(
            $seat->id,
            $request->validated()
        );

        return new SeatResource($seatData);
    }

    public function destroy(Seat $seat): JsonResponse
    {
        $this->seatService->delete($seat->id);

        return response()->json([
            'message' => 'Seat deleted successfully.',
        ]);
    }
}
