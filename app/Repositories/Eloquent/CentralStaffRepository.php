<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\BaseRepository;
use App\Repositories\Interface\CentralStaffInterface;
use Spatie\Permission\Models\Role;

class CentralStaffRepository extends BaseRepository implements CentralStaffInterface
{
    protected array $allowedSorts = [
        'id',
        'name',
        'email',
        'created_at',
        'updated_at',
    ];

    public function getAll(array $filters = []): array
    {
        $query = User::query()->with(['roles', 'permissions']);

        if (
            ! isset($filters['sort_by'])
            || ! in_array($filters['sort_by'], $this->allowedSorts, true)
        ) {
            $query->latest('id');
        }

        return $this->getPaginated($query, $filters);
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function findWithRelations(int $id): ?User
    {
        return User::with(['roles', 'permissions'])
            ->find($id);
    }

    public function findWithRelationsOrFail(int $id): User
    {
        return User::with(['roles', 'permissions'])
            ->findOrFail($id);
    }

    public function findOrFail(int $id): User
    {
        return User::findOrFail($id);
    }

    public function findRole(string $roleName): ?Role
    {
        return Role::where('name', $roleName)
            ->where('guard_name', 'api')
            ->first();
    }

    public function findRoleOrFail(string $roleName): Role
    {
        return Role::where('name', $roleName)
            ->where('guard_name', 'api')
            ->firstOrFail();
    }

    public function loadRelations(User $user): User
    {
        return $user->load(['roles', 'permissions']);
    }

    public function update(User $user, array $data): bool
    {
        return $user->update($data);
    }

    public function freshWithRelations(User $user): User
    {
        return $user->fresh(['roles', 'permissions']);
    }

    public function delete(User $user): bool
    {
        return $user->delete();
    }
}
