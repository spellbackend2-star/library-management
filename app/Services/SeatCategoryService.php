<?php

namespace App\Services;

use App\Models\SeatCategory;
use App\Repositories\Interface\SeatCategoryInterface;

class SeatCategoryService
{
    public function __construct(
        protected SeatCategoryInterface $seatCategoryRepository
    ) {}

    public function getAll()
    {
        return $this->seatCategoryRepository->all();
    }

    public function getById(int $id): ?SeatCategory
    {
        return $this->seatCategoryRepository->find($id);
    }

    public function create(array $data): SeatCategory
    {
        return $this->seatCategoryRepository->create($data);
    }

    public function update(int $id, array $data): SeatCategory
    {
        return $this->seatCategoryRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->seatCategoryRepository->delete($id);
    }
}
