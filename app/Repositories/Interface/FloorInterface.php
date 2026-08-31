<?php

namespace App\Repositories\Interface;

use App\Models\Floor;

interface FloorInterface
{
    public function all();

    public function find(int $id): ?Floor;

    public function create(array $data): Floor;

    public function update(int $id, array $data): Floor;

    public function delete(int $id): bool;
}
