<?php

namespace App\Repositories\Eloquent;

use App\Models\Staff;
use App\Repositories\Interface\StaffInterface;

class StaffRepository implements StaffInterface
{
    public function all()
    {
        return Staff::latest()->get();
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
