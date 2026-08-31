<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Http\Requests\Booking\UpdateBookingRequest;
use App\Http\Resources\BookingResource;
use App\Services\BookingService;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;

class BookingController extends Controller
{
    public function __construct(
        protected BookingService $bookingService
    ) {}

    public function index()
    {
        return BookingResource::collection(
            $this->bookingService->getAll()
        );
    }

    public function store(StoreBookingRequest $request): BookingResource
    {
        $booking = $this->bookingService->create(
            $request->validated()
        );

        return new BookingResource($booking);
    }

    public function show(Booking $booking): BookingResource
    {
        $booking = $this->bookingService->getById($booking->id);

        return new BookingResource($booking);
    }

    public function update(
        UpdateBookingRequest $request,
        Booking $booking
    ): BookingResource {
        $bookingData = $this->bookingService->update(
            $booking->id,
            $request->validated()
        );

        return new BookingResource($bookingData);
    }

    public function destroy(Booking $booking): JsonResponse
    {
        $this->bookingService->delete($booking->id);

        return response()->json([
            'message' => 'Booking deleted successfully.',
        ]);
    }
}
