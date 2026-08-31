<?php

namespace App\Services;

use App\Models\SeatBooking;
use App\Repositories\Interface\SeatBookingInterface;

class SeatBookingService
{
    public function __construct(
        protected SeatBookingInterface $seatBookingRepository
    ) {}

    public function getAll()
    {
        return $this->seatBookingRepository->all();
    }

    public function getById(int $id): ?SeatBooking
    {
        return $this->seatBookingRepository->find($id);
    }

    public function create(array $data): SeatBooking
    {
        return $this->seatBookingRepository->create($data);
    }

    public function update(int $id, array $data): SeatBooking
    {
        return $this->seatBookingRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->seatBookingRepository->delete($id);
    }
}
