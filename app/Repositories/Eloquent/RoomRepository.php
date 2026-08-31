<?php

namespace App\Repositories\Eloquent;

use App\Models\Room;
use App\Repositories\Interface\RoomInterface;

class RoomRepository implements RoomInterface
{
    public function all()
    {
        return Room::latest()->get();
    }

    public function find(int $id): ?Room
    {
        return Room::find($id);
    }

    public function create(array $data): Room
    {
        return Room::create($data);
    }

    public function update(int $id, array $data): Room
    {
        $room = Room::findOrFail($id);

        $room->update($data);

        return $room->fresh();
    }

    public function delete(int $id): bool
    {
        return Room::findOrFail($id)->delete();
    }
}
