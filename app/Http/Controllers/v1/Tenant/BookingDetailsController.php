<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingSeatResource;
use App\Http\Resources\BorrowResource;
use App\Http\Resources\LockerAssigmentsResource;
use App\Services\BookingService;
use App\Traits\ResponseMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingDetailsController extends Controller
{
    use ResponseMessage;

    public function __construct(
        protected BookingService $bookingService
    ) {}

    public function seatBookings(Request $request, int $booking): JsonResponse
    {
        try {
            $filters = $request->only([
                'sort_by',
                'sort_order',
                'per_page',
            ]);
            $result = $this->bookingService->getSeatBookingsByBooking($booking, $filters);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }

        return $this->successResponse(
            [
                'booking_id' => $booking,
                'seats' => BookingSeatResource::collection($result['data']),
            ],
            'Seat bookings retrieved successfully.',
            200,
            $result['meta'] ?? null
        );
    }

    public function borrows(Request $request, int $booking): JsonResponse
    {
        try {
            $rows = $this->bookingService->getBorrowsByBooking($booking);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }

        return $this->successResponse(
            [
                'booking_id' => $booking,
                'borrows' => BorrowResource::collection($rows),
            ],
            'Borrows retrieved successfully.'
        );
    }

    public function allSeatBookings(Request $request): JsonResponse
    {
        $filters = $request->only([
            'booking_id',
            'seat_id',
            'status',
            'sort_by',
            'sort_order',
            'per_page',
        ]);

        $result = $this->bookingService->getAllSeatBookings($filters);

        return $this->successResponse(
            [
                'seats' => BookingSeatResource::collection($result['data']),
            ],
            'All seat bookings retrieved successfully.',
            200,
            $result['meta']
        );
    }

    public function completeSeatBooking(Request $request, int $seat): JsonResponse
    {
        try {
            $row = $this->bookingService->completeSeatBooking($seat);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }

        return $this->successResponse(
            new BookingSeatResource($row),
            'Seat booking completed.'
        );
    }

    public function lockerAssignments(Request $request, int $booking): JsonResponse
    {
        try {
            $rows = $this->bookingService->getLockerAssignmentsByBooking($booking);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }

        return $this->successResponse(
            [
                'booking_id' => $booking,
                'lockers' => LockerAssigmentsResource::collection($rows),
            ],
            'Locker assignments retrieved successfully.'
        );
    }
}
