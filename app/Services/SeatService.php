<?php

namespace App\Services;

use App\Models\Seat;
use App\Repositories\Interface\SeatInterface;

class SeatService
{
    public function __construct(
        protected SeatInterface $seatRepository
    ) {}

    public function getAll()
    {
        return $this->seatRepository->all();
    }

    public function getById(int $id): ?Seat
    {
        return $this->seatRepository->find($id);
    }

    public function create(array $data): Seat
    {
        return $this->seatRepository->create($data);
    }

    public function update(int $id, array $data): Seat
    {
        return $this->seatRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->seatRepository->delete($id);
    }
}
