<?php

namespace App\Repositories\Interface;

use App\Models\Borrow;

interface BorrowInterface
{
    public function all();

    public function find(int $id): ?Borrow;

    public function create(array $data): Borrow;

    public function update(int $id, array $data): Borrow;

    public function delete(int $id): bool;

    public function byBooking(int $bookingId);
}
