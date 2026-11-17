<?php

namespace App\Services;

use App\Models\LockerAssignment;
use App\Repositories\Interface\LockerAssignmentInterface;
use Illuminate\Support\Facades\DB;

class LockerAssignmentService
{
    public function __construct(
        protected LockerAssignmentInterface $lockerAssignmentRepository,
    ) {}

    public function getAll(array $filters = []): array
    {
        return $this->lockerAssignmentRepository->getAll($filters);
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
        return DB::transaction(function () use ($id, $data) {
            $assignment = $this->lockerAssignmentRepository->find($id);

            if (!$assignment) {
                throw new \Exception('Locker assignment not found.');
            }

            $assignment = $this->lockerAssignmentRepository->update($id, $data);

            return $assignment->fresh();
        });
    }

    public function delete(int $id): bool
    {
        return $this->lockerAssignmentRepository->delete($id);
    }
}
