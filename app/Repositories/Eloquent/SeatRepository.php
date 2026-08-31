<?php

namespace App\Repositories\Eloquent;

use App\Models\Seat;
use App\Repositories\Interface\SeatInterface;

class SeatRepository implements SeatInterface
{
    public function all()
    {
        return Seat::latest()->get();
    }

    public function find(int $id): ?Seat
    {
        return Seat::find($id);
    }

    public function create(array $data): Seat
    {
        return Seat::create($data);
    }

    public function update(int $id, array $data): Seat
    {
        $seat = Seat::findOrFail($id);

        $seat->update($data);

        return $seat->fresh();
    }

    public function delete(int $id): bool
    {
        return Seat::findOrFail($id)->delete();
    }
}
