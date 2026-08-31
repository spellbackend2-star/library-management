<?php

namespace App\Repositories\Eloquent;

use App\Models\SeatBooking;
use App\Repositories\Interface\SeatBookingInterface;

class SeatBookingRepository implements SeatBookingInterface
{
    public function all()
    {
        return SeatBooking::latest()->get();
    }

    public function find(int $id): ?SeatBooking
    {
        return SeatBooking::find($id);
    }

    public function create(array $data): SeatBooking
    {
        return SeatBooking::create($data);
    }

    public function update(int $id, array $data): SeatBooking
    {
        $booking = SeatBooking::findOrFail($id);

        $booking->update($data);

        return $booking->fresh();
    }

    public function delete(int $id): bool
    {
        return SeatBooking::findOrFail($id)->delete();
    }
}
