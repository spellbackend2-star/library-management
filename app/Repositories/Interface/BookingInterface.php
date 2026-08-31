<?php

namespace App\Repositories\Interface;

use App\Models\Booking;

interface BookingInterface
{
    public function all();

    public function find(int $id): ?Booking;

    public function create(array $data): Booking;

    public function update(int $id, array $data): Booking;

    public function delete(int $id): bool;
}
