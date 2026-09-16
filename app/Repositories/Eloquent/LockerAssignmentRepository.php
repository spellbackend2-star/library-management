<?php

namespace App\Repositories\Eloquent;

use App\Models\LockerAssignment;
use App\Repositories\BaseRepository;
use App\Repositories\Interface\LockerAssignmentInterface;

class LockerAssignmentRepository extends BaseRepository implements LockerAssignmentInterface
{
    protected array $allowedSorts = [
        'id',
        'booking_id',
        'locker_id',
        'member_id',
        'assigned_date',
        'expiry_date',
        'status',
        'created_at',
        'updated_at',
    ];

    protected array $allowedFilters = [
        'booking_id' => [
            'type' => 'exact',
            'column' => 'booking_id',
        ],
        'locker_id' => [
            'type' => 'exact',
            'column' => 'locker_id',
        ],
        'member_id' => [
            'type' => 'exact',
            'column' => 'member_id',
        ],
        'status' => [
            'type' => 'exact',
            'column' => 'status',
        ],
    ];

    public function all()
    {
        return LockerAssignment::latest()->get();
    }

    public function getAll(array $filters = []): array
    {
        $query = LockerAssignment::with(['locker', 'member']);

        return $this->getPaginated($query, $filters);
    }

    public function find(int $id): ?LockerAssignment
    {
        return LockerAssignment::with(['locker', 'member'])->find($id);
    }

    public function create(array $data): LockerAssignment
    {
        return LockerAssignment::create($data);
    }

    public function update(int $id, array $data): LockerAssignment
    {
        $assignment = LockerAssignment::findOrFail($id);

        $assignment->update($data);

        return $assignment->fresh();
    }

    public function delete(int $id): bool
    {
        return LockerAssignment::findOrFail($id)->delete();
    }

    public function byBooking(int $bookingId, array $filters = []): array
    {
        $query = LockerAssignment::with(['locker', 'member'])
            ->where('booking_id', $bookingId);

        return $this->getPaginated($query, $filters);
    }
}
