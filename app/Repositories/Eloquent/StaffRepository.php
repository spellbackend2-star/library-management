<?php

namespace App\Repositories\Eloquent;

use App\Models\Staff;
use App\Repositories\BaseRepository;
use App\Repositories\Interface\StaffInterface;

class StaffRepository extends BaseRepository implements StaffInterface
{
    protected array $allowedSorts = [
        'id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'hire_date',
        'is_active',
        'created_at',
    ];

    public function all()
    {
        return Staff::latest()->get();
    }

    public function getAll(array $filters = [])
    {
        $query = Staff::query()->with('user.roles');

        if (isset($filters['search']) && $filters['search'] !== '') {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', '%'. $search .'%')
                    ->orWhere('last_name', 'like', '%'. $search .'%')
                    ->orWhere('email', 'like', '%'. $search .'%')
                   
                ;
            });
        }

        if (isset($filters['role']) && $filters['role'] !== '') {
            $query->whereHas('user.roles', function ($q) use ($filters) {
                $q->where('name', $filters['role']);
            });
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $value = strtolower($filters['status']);

            if (in_array($value, ['active', '1', 'true', 'yes'], true)) {
                $query->where('is_active', true);
            } elseif (in_array($value, ['inactive', '0', 'false', 'no'], true)) {
                $query->where('is_active', false);
            }
        }

        $query = $this->applySorting($query, $filters);

        $paginator = $this->applyPagination($query, $filters);

        return [
            'data' => $paginator->items(),
            'meta' => $this->paginationMeta($paginator),
        ];
    }

    public function find(int $id): ?Staff
    {
        return Staff::find($id);
    }

    public function create(array $data): Staff
    {
        return Staff::create($data);
    }

    public function update(int $id, array $data): Staff
    {
        $staff = Staff::findOrFail($id);

        $staff->update($data);

        return $staff->fresh();
    }

    public function delete(int $id): bool
    {
        return Staff::findOrFail($id)->delete();
    }
}
