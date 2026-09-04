<?php

namespace App\Services;

use App\Models\Borrow;
use App\Repositories\Interface\BorrowInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BorrowService
{
    public function __construct(
        protected BorrowInterface $borrowRepository,
        protected FineService $fineService,
    ) {}

    public function getAll()
    {
        return $this->borrowRepository->all();
    }

    public function getById(int $id): ?Borrow
    {
        return $this->borrowRepository->find($id);
    }

    public function create(array $data): Borrow
    {
        return $this->borrowRepository->create($data);
    }

    public function update(int $id, array $data): Borrow
    {
        return DB::transaction(function () use ($id, $data) {
            $borrow = $this->borrowRepository->find($id);

            if (!$borrow) {
                throw new \Exception('Borrow not found.');
            }

            $newStatus = $data['status'] ?? $borrow->status;

            $borrow = $this->borrowRepository->update($id, $data);

            // Trigger fine creation if status transitions to a terminal
            // "done" state ('returned' or 'lost') and the actual return
            // date is past the due date.
            if (in_array($newStatus, ['returned', 'lost'], true)) {
                $returnDate = $data['return_date']
                    ?? $borrow->return_date
                    ?? Carbon::now()->toDateString();

                $borrow->refresh();

                $this->fineService->fineForBorrowOnReturn(
                    borrow: $borrow,
                    returnDate: $returnDate,
                );
            }

            return $borrow->fresh();
        });
    }

    public function delete(int $id): bool
    {
        return $this->borrowRepository->delete($id);
    }
}
