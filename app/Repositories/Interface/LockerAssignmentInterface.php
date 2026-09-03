<?php

namespace App\Repositories\Interface;

use App\Models\LockerAssignment;

interface LockerAssignmentInterface
{
    public function all();


    public function create(array $data): LockerAssignment;

    public function update(int $id, array $data): LockerAssignment;

    public function delete(int $id): bool;

    public function byBooking(int $bookingId);
}
