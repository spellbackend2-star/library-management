<?php

namespace App\Repositories\Interface;

use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Role;

interface CentralRoleInterface
{
    public function getAll(array $filters = []): array;

    public function getAllWithoutPagination(): Collection;

    public function findById(int $id): Role;
}
