<?php

namespace App\Repositories\Interface;

use App\Models\Book;

interface BookInterface
{
    public function all();

    public function find(int $id): ?Book;

    public function create(array $data): Book;

    public function update(int $id, array $data): Book;

    public function delete(int $id): bool;
}
