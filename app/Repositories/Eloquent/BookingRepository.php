<?php

namespace App\Repositories\Eloquent;

use App\Models\Booking;
use App\Repositories\Interface\BookingInterface;

class BookingRepository implements BookingInterface
{
    public function all()
    {
        return Booking::with(['member', 'package', 'bookingSeats', 'borrows', 'lockerAssignments'])->latest()->get();
    }

    public function find(int $id): ?Booking
    {
        return Booking::with(['member', 'package', 'bookingSeats', 'borrows', 'lockerAssignments'])->find($id);
    }

    public function create(array $data): Booking
    {
        return Booking::create($data);
    }

    public function update(int $id, array $data): Booking
    {
        $booking = Booking::findOrFail($id);

        $booking->update($data);

        return $booking->fresh(['member', 'package', 'bookingSeats', 'borrows', 'lockerAssignments']);
    }

    public function delete(int $id): bool
    {
        return Booking::findOrFail($id)->delete();
    }
}
