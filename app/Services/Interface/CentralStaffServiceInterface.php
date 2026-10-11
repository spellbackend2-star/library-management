<?php

namespace App\Services\Interface;

use App\Models\User;

interface CentralStaffServiceInterface
{
    public function getAll(array $filters = []): array;

    public function create(array $data): User;

    public function find(int $id): ?User;

    public function update(int $id, array $data): User;

    public function delete(int $id): void;

    public function assignRole(int $staffId, string $roleName): User;
}
