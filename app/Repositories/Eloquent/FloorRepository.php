<?php

namespace App\Repositories\Eloquent;

use App\Models\Floor;
use App\Repositories\Interface\FloorInterface;

class FloorRepository implements FloorInterface
{
    public function all()
    {
        return Floor::latest()->get();
    }

    public function find(int $id): ?Floor
    {
        return Floor::find($id);
    }

    public function create(array $data): Floor
    {
        return Floor::create($data);
    }

    public function update(int $id, array $data): Floor
    {
        $floor = Floor::findOrFail($id);

        $floor->update($data);

        return $floor->fresh();
    }

    public function delete(int $id): bool
    {
        return Floor::findOrFail($id)->delete();
    }
}
