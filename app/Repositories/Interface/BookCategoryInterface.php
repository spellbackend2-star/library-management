<?php

namespace App\Repositories\Interface;

use App\Models\BookCategory;

interface BookCategoryInterface
{
    public function all();

    public function find(int $id): ?BookCategory;

    public function create(array $data): BookCategory;

    public function update(int $id, array $data): BookCategory;

    public function delete(int $id): bool;
}
