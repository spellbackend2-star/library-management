<?php

namespace App\Services;

use App\Models\LockerAssignment;
use App\Repositories\Interface\LockerAssignmentInterface;

class LockerAssignmentService
{
    public function __construct(
        protected LockerAssignmentInterface $lockerAssignmentRepository
    ) {}

    public function getAll()
    {
        return $this->lockerAssignmentRepository->all();
    }

    public function getById(int $id): ?LockerAssignment
    {
        return $this->lockerAssignmentRepository->find($id);
    }

    public function create(array $data): LockerAssignment
    {
        return $this->lockerAssignmentRepository->create($data);
    }

    public function update(int $id, array $data): LockerAssignment
    {
        return $this->lockerAssignmentRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->lockerAssignmentRepository->delete($id);
    }
}
