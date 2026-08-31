<?php

namespace App\Repositories\Eloquent;

use App\Models\Locker;
use App\Repositories\Interface\LockerInterface;

class LockerRepository implements LockerInterface
{
    public function all()
    {
        return Locker::latest()->get();
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
