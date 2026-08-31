<?php

namespace App\Repositories\Interface;

use App\Models\SeatCategory;

interface SeatCategoryInterface
{
    public function all();

    public function find(int $id): ?SeatCategory;

    public function create(array $data): SeatCategory;

    public function update(int $id, array $data): SeatCategory;

    public function delete(int $id): bool;
}
