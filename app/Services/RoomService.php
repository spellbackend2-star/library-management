<?php

namespace App\Services;

use App\Models\Room;
use App\Repositories\Interface\RoomInterface;

class RoomService
{
    public function __construct(
        protected RoomInterface $roomRepository
    ) {}

    public function getAll()
    {
        return $this->roomRepository->all();
    }

    public function getById(int $id): ?Room
    {
        return $this->roomRepository->find($id);
    }

    public function create(array $data): Room
    {
        return $this->roomRepository->create($data);
    }

    public function update(int $id, array $data): Room
    {
        return $this->roomRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->roomRepository->delete($id);
    }
}
