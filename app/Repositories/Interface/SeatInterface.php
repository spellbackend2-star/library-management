<?php

namespace App\Repositories\Interface;

use App\Models\Seat;

interface SeatInterface
{
    public function all();

    public function find(int $id): ?Seat;

    public function create(array $data): Seat;

    public function update(int $id, array $data): Seat;

    public function delete(int $id): bool;
}
