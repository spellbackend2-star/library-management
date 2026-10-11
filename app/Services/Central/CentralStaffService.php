<?php

namespace App\Services\Central;

use App\Models\User;
use App\Repositories\Interface\CentralStaffInterface;
use App\Services\Interface\CentralStaffServiceInterface;
use Illuminate\Support\Facades\DB;

class CentralStaffService implements CentralStaffServiceInterface
{
    public function __construct(
        protected CentralStaffInterface $centralStaffRepository
    ) {}

    public function getAll(array $filters = []): array
    {
        return $this->centralStaffRepository->getAll($filters);
    }

    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = $this->centralStaffRepository->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);

            $role = $this->centralStaffRepository->findRole($data['role']);

            if ($role) {
                $user->assignRole($role);
            }

            return $this->centralStaffRepository->loadRelations($user);
        });
    }

    public function find(int $id): ?User
    {
        return $this->centralStaffRepository->findWithRelations($id);
    }

    public function update(int $id, array $data): User
    {
        $staffData = $this->centralStaffRepository
            ->findWithRelationsOrFail($id);

        return DB::transaction(function () use ($data, $staffData) {
            $validated = $data;
            $role = $validated['role'] ?? null;
            unset($validated['role']);

            $this->centralStaffRepository->update($staffData, $validated);

            if ($role) {
                $staffData->syncRoles([$role]);
            }

            return $this->centralStaffRepository
                ->freshWithRelations($staffData);
        });
    }

    public function delete(int $id): void
    {
        $staffData = $this->centralStaffRepository->findOrFail($id);

        DB::transaction(function () use ($staffData) {
            $this->centralStaffRepository->delete($staffData);
        });
    }

    public function assignRole(int $staffId, string $roleName): User
    {
        $staffData = $this->centralStaffRepository
            ->findWithRelationsOrFail($staffId);

        $role = $this->centralStaffRepository->findRoleOrFail($roleName);

        $staffData->syncRoles([$role->name]);

        return $this->centralStaffRepository
            ->freshWithRelations($staffData);
    }
}
