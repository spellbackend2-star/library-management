<?php

namespace App\Repositories\Interface;

use App\Models\SeatBooking;

interface SeatBookingInterface
{
    public function all();

    public function find(int $id): ?SeatBooking;

    public function create(array $data): SeatBooking;

    public function update(int $id, array $data): SeatBooking;

    public function delete(int $id): bool;
}
