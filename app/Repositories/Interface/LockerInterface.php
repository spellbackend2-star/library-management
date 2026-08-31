<?php

namespace App\Repositories\Interface;

use App\Models\Locker;

interface LockerInterface
{
    public function all();

    public function find(int $id): ?Locker;

    public function create(array $data): Locker;

    public function update(int $id, array $data): Locker;

    public function delete(int $id): bool;
}
