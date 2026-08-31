<?php

namespace App\Services;

use App\Models\LockerAssigments;
use App\Repositories\Interface\LockerAssignmentInterface;

class LockerAssigmentsService
{
    public function __construct(
        protected LockerAssignmentInterface $lockerAssigmentsRepository
    ) {}

    public function getAll()
    {
        return $this->lockerAssigmentsRepository->all();
    }

    public function getById(int $id): ?LockerAssigments
    {
        return $this->lockerAssigmentsRepository->find($id);
    }

    public function create(array $data): LockerAssigments
    {
        return $this->lockerAssigmentsRepository->create($data);
    }

    public function update(int $id, array $data): LockerAssigments
    {
        return $this->lockerAssigmentsRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->lockerAssigmentsRepository->delete($id);
    }
}
