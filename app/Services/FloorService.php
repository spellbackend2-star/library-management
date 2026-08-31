<?php

namespace App\Services;

use App\Models\Floor;
use App\Repositories\Interface\FloorInterface;

class FloorService
{
    public function __construct(
        protected FloorInterface $floorRepository
    ) {}

    public function getAll()
    {
        return $this->floorRepository->all();
    }

    public function getById(int $id): ?Floor
    {
        return $this->floorRepository->find($id);
    }

    public function create(array $data): Floor
    {
        return $this->floorRepository->create($data);
    }

    public function update(int $id, array $data): Floor
    {
        return $this->floorRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->floorRepository->delete($id);
    }
}
