<?php

namespace App\Services;

use App\Models\Locker;
use App\Repositories\Interface\LockerInterface;

class LockerService
{
    public function __construct(
        protected LockerInterface $lockerRepository
    ) {}

    public function getAll()
    {
        return $this->lockerRepository->all();
    }

    public function getById(int $id): ?Locker
    {
        return $this->lockerRepository->find($id);
    }

    public function create(array $data): Locker
    {
        return $this->lockerRepository->create($data);
    }

    public function update(int $id, array $data): Locker
    {
        return $this->lockerRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->lockerRepository->delete($id);
    }
}
