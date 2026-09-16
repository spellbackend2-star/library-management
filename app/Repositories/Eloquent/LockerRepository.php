<?php

namespace App\Repositories\Eloquent;

use App\Models\Locker;
use App\Repositories\BaseRepository;
use App\Repositories\Interface\LockerInterface;

class LockerRepository extends BaseRepository implements LockerInterface
{
    protected array $allowedSorts = [
        'id',
        'locker_number',
        'locker_type',
        'status',
        'created_at',
        'updated_at',
    ];

    protected array $allowedFilters = [
        'floor_id' => [
            'type' => 'exact',
            'column' => 'floor_id',
        ],
        'locker_type' => [
            'type' => 'exact',
            'column' => 'locker_type',
        ],
        'status' => [
            'type' => 'exact',
            'column' => 'status',
        ],
        'search' => [
            'type' => 'like',
            'column' => 'locker_number',
        ],
    ];

    public function all()
    {
        return Locker::latest()->get();
    }

    public function getAll(array $filters = []): array
    {
        $query = Locker::query();

        return $this->getPaginated($query, $filters);
    }

    public function find(int $id): ?Locker
    {
        return Locker::find($id);
    }

    public function create(array $data): Locker
    {
        return Locker::create($data);
    }

    public function update(int $id, array $data): Locker
    {
        $locker = Locker::findOrFail($id);

        $locker->update($data);

        return $locker->fresh();
    }

    public function delete(int $id): bool
    {
        return Locker::findOrFail($id)->delete();
    }
}
