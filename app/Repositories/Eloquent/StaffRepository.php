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
        'updated_at',
    ];

    public function all()
    {
        return Staff::with('user.roles')
            ->latest()
            ->get();
    }

    public function getAll(array $filters = [])
    {
        $query = Staff::query()
            ->with('user.roles');

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */
        if (! empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($query) use ($search) {
                $query->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");

                if (is_numeric($search)) {
                    $query->orWhere('id', (int) $search);
                }
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Role Filter
        |--------------------------------------------------------------------------
        */
        if (! empty($filters['role'])) {
            $query->whereHas('user.roles', function ($query) use ($filters) {
                $query->where('name', $filters['role']);
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Status Filter
        |--------------------------------------------------------------------------
        */
        if (! empty($filters['status'])) {
            $status = strtolower($filters['status']);

            if (in_array($status, ['active', '1', 'true', 'yes'], true)) {
                $query->where('is_active', true);
            } elseif (in_array($status, ['inactive', '0', 'false', 'no'], true)) {
                $query->where('is_active', false);
            }
        }

        return $this->getPaginated($query, $filters);
    }

    public function find(int $id): ?Staff
    {
        return Staff::with('user.roles')
            ->find($id);
    }

    public function create(array $data): Staff
    {
        return Staff::create($data);
    }

    public function update(int $id, array $data): Staff
    {
        $staff = Staff::findOrFail($id);

        $staff->update($data);

        return $staff->fresh([
            'user.roles',
        ]);
    }

    public function delete(int $id): bool
    {
        return Staff::findOrFail($id)->delete();
    }
}