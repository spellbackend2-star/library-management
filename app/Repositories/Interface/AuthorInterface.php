<?php

namespace App\Repositories\Interface;

use App\Models\Author;

interface AuthorInterface
{
    public function all();

    public function find(int $id): ?Author;

    public function create(array $data): Author;

    public function update(int $id, array $data): Author;

    public function delete(int $id): bool;
}
