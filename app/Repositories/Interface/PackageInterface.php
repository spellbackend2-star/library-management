<?php

namespace App\Repositories\Interface;

use App\Models\Package;

interface PackageInterface
{
    public function all();

    public function find(int $id): ?Package;

    public function create(array $data): Package;

    public function update(int $id, array $data): Package;

    public function delete(int $id): bool;
}
