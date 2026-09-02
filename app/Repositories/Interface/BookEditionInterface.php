<?php

namespace App\Repositories\Interface;

use App\Models\BookEdition;

interface BookEditionInterface
{
    public function all();

    public function find(int $id): ?BookEdition;

    public function create(array $data): BookEdition;

    public function update(int $id, array $data): BookEdition;

    public function delete(int $id): bool;

    public function deleteNotIn(int $bookId, array $keptIds): void;
}
