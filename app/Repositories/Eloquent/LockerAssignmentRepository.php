<?php

namespace App\Repositories\Eloquent;

use App\Models\LockerAssignment;
use App\Repositories\Interface\LockerAssignmentInterface;

class LockerAssignmentRepository implements LockerAssignmentInterface
{
    public function all()
    {
        return LockerAssignment::latest()->get();
    }

    public function find(int $id): ?LockerAssignment
    {
        return LockerAssignment::find($id);
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
}
