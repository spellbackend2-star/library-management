<?php

namespace App\Repositories\Interface;

use App\Models\BookAuthor;

interface BookAuthorInterface
{
    public function all();

    public function find(int $id): ?BookAuthor;

    public function create(array $data): BookAuthor;

    public function update(int $id, array $data): BookAuthor;

    public function delete(int $id): bool;
}
