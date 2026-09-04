<?php

namespace App\Services;

use App\Models\LockerAssignment;
use App\Repositories\Interface\LockerAssignmentInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LockerAssignmentService
{
    public function __construct(
        protected LockerAssignmentInterface $lockerAssignmentRepository,
        protected FineService $fineService,
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
        return DB::transaction(function () use ($id, $data) {
            $assignment = $this->lockerAssignmentRepository->find($id);

            if (!$assignment) {
                throw new \Exception('Locker assignment not found.');
            }

            $newStatus = $data['status'] ?? $assignment->status;

            $assignment = $this->lockerAssignmentRepository->update($id, $data);

            if (in_array($newStatus, ['returned', 'expired'], true)) {
                $returnDate = $data['returned_date']
                    ?? $assignment->returned_date
                    ?? Carbon::now()->toDateString();

                $assignment->refresh();

                $this->fineService->fineForLockerOnReturn(
                    assignment: $assignment,
                    returnDate: $returnDate,
                );
            }

            return $assignment->fresh();
        });
    }

    public function delete(int $id): bool
    {
        return $this->lockerAssignmentRepository->delete($id);
    }
}
