<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\SeatBooking\StoreSeatBookingRequest;
use App\Http\Requests\SeatBooking\UpdateSeatBookingRequest;
use App\Http\Resources\SeatBookingResource;
use App\Services\SeatBookingService;
use App\Models\SeatBooking;
use Illuminate\Http\JsonResponse;

class SeatBookingController extends Controller
{
    public function __construct(
        protected SeatBookingService $seatBookingService
    ) {}

    public function index()
    {
        return SeatBookingResource::collection(
            $this->seatBookingService->getAll()
        );
    }

    public function store(StoreSeatBookingRequest $request): SeatBookingResource
    {
        $booking = $this->seatBookingService->create(
            $request->validated()
        );

        return new SeatBookingResource($booking);
    }

    public function show(SeatBooking $seat_booking): SeatBookingResource
    {
        return new SeatBookingResource($seat_booking);
    }

    public function update(
        UpdateSeatBookingRequest $request,
        SeatBooking $seat_booking
    ): SeatBookingResource {
        $bookingData = $this->seatBookingService->update(
            $seat_booking->id,
            $request->validated()
        );

        return new SeatBookingResource($bookingData);
    }

    public function destroy(SeatBooking $seat_booking): JsonResponse
    {
        $this->seatBookingService->delete($seat_booking->id);

        return response()->json([
            'message' => 'Seat booking deleted successfully.',
        ]);
    }
}
