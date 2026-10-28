<?php

namespace App\Repositories\Eloquent;

use App\Repositories\BaseRepository;
use App\Repositories\Interface\CentralRoleInterface;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Role;

class CentralRoleRepository extends BaseRepository implements CentralRoleInterface
{
    protected array $allowedSorts = [
        'id',
        'name',
        'created_at',
        'updated_at',
    ];

    public function getAll(array $filters = []): array
    {
        $query = Role::query()
            ->where('guard_name', 'api')
            ->with('permissions:id,name,guard_name');

        if (!isset($filters['sort_by']) || !in_array($filters['sort_by'], $this->allowedSorts, true)) {
            $query->orderBy('name');
        }

        return $this->getPaginated($query, $filters);
    }

    public function getAllWithoutPagination(): Collection
    {
        return Role::query()
            ->where('guard_name', 'api')
            ->with('permissions:id,name,guard_name')
            ->orderBy('name')
            ->get();
    }

    public function findById(int $id): Role
    {
        return Role::query()
            ->where('guard_name', 'api')
            ->with('permissions:id,name,guard_name')
            ->findOrFail($id);
    }
}
