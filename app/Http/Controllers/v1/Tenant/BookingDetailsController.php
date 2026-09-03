<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingSeatResource;
use App\Http\Resources\BorrowResource;
use App\Http\Resources\LockerAssigmentsResource;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingDetailsController extends Controller
{
    public function __construct(
        protected BookingService $bookingService
    ) {}

    public function seatBookings(Request $request, int $booking): JsonResponse
    {
        try {
            $rows = $this->bookingService->getSeatBookingsByBooking($booking);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }

        return response()->json([
            'booking_id' => $booking,
            'seats' => BookingSeatResource::collection($rows),
        ]);
    }

    public function borrows(Request $request, int $booking): JsonResponse
    {
        try {
            $rows = $this->bookingService->getBorrowsByBooking($booking);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }

        return response()->json([
            'booking_id' => $booking,
            'borrows' => BorrowResource::collection($rows),
        ]);
    }

    public function allSeatBookings(): JsonResponse
    {
        return response()->json([
            'seats' => BookingSeatResource::collection(
                $this->bookingService->getAllSeatBookings()
            ),
        ]);
    }

    public function lockerAssignments(Request $request, int $booking): JsonResponse
    {
        try {
            $rows = $this->bookingService->getLockerAssignmentsByBooking($booking);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }

        return response()->json([
            'booking_id' => $booking,
            'lockers' => LockerAssigmentsResource::collection($rows),
        ]);
    }
}
