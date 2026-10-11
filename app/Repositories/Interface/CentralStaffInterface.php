<?php

namespace App\Repositories\Interface;

use App\Models\User;
use Spatie\Permission\Models\Role;

interface CentralStaffInterface
{
    public function getAll(array $filters = []): array;

    public function create(array $data): User;

    public function findWithRelations(int $id): ?User;

    public function findWithRelationsOrFail(int $id): User;

    public function findOrFail(int $id): User;

    public function findRole(string $roleName): ?Role;

    public function findRoleOrFail(string $roleName): Role;

    public function loadRelations(User $user): User;

    public function update(User $user, array $data): bool;

    public function freshWithRelations(User $user): User;

    public function delete(User $user): bool;
}
