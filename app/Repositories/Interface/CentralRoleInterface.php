<?php

namespace App\Repositories\Interface;

use Spatie\Permission\Models\Role;

interface CentralRoleInterface
{
    public function getAll(array $filters = []): array;

    public function findById(int $id): Role;
}
