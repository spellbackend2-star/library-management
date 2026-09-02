<?php

namespace App\Repositories\Interface;

use App\Models\Copy;

interface CopyInterface
{
    public function all();

    public function find(int $id): ?Copy;

    public function create(array $data): Copy;

    public function update(int $id, array $data): Copy;

    public function delete(int $id): bool;
    public function deleteNotIn(int $editionId, array $keptIds): void;

}
