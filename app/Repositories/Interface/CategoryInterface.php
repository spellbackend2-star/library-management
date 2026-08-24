<?php

namespace App\Repositories\Interface;

use App\Models\Category;

interface CategoryInterface
{
    public function all();

    public function find(int $id): ?Category;

    public function create(array $data): Category;

    public function update(int $id, array $data): Category;

    public function delete(int $id): bool;
}
