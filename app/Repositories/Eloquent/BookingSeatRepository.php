<?php

namespace App\Repositories\Eloquent;

use App\Models\BookingSeat;
use App\Repositories\Interface\BookingSeatInterface;

class BookingSeatRepository implements BookingSeatInterface
{
    public function all()
    {
        return BookingSeat::with(['booking', 'seat'])->latest()->get();
    }

    public function find(int $id): ?BookingSeat
    {
        return BookingSeat::with(['booking', 'seat'])->find($id);
    }

    public function create(array $data): BookingSeat
    {
        return BookingSeat::create($data);
    }

    public function update(int $id, array $data): BookingSeat
    {
        $bookingSeat = BookingSeat::findOrFail($id);

        $bookingSeat->update($data);

        return $bookingSeat->fresh(['booking', 'seat']);
    }

    public function delete(int $id): bool
    {
        return BookingSeat::findOrFail($id)->delete();
    }

    public function byBooking(int $bookingId)
    {
        return BookingSeat::with(['seat'])
            ->where('booking_id', $bookingId)
            ->latest()
            ->get();
    }
}
