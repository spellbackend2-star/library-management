<?php

namespace App\Repositories\Interface;

use App\Models\BookingSeat;

interface BookingSeatInterface
{
    public function all();

    public function find(int $id): ?BookingSeat;

    public function create(array $data): BookingSeat;

    public function update(int $id, array $data): BookingSeat;

    public function delete(int $id): bool;
}
