<?php

namespace App\Repositories\Interface;

use App\Models\Room;

interface RoomInterface
{
    public function all();

    public function find(int $id): ?Room;

    public function create(array $data): Room;

    public function update(int $id, array $data): Room;

    public function delete(int $id): bool;
}
