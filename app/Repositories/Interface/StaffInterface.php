<?php

namespace App\Repositories\Interface;

use App\Models\Staff;

interface StaffInterface
{
    public function all();

    public function find(int $id): ?Staff;

    public function create(array $data): Staff;

    public function update(int $id, array $data): Staff;

    public function delete(int $id): bool;
}
