<?php

namespace App\Services;

use App\Models\Borrow;
use App\Models\Copy;
use App\Repositories\Interface\BorrowInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BorrowService
{
    public function __construct(
        protected BorrowInterface $borrowRepository,
        protected FineService $fineService,
    ) {}

    public function getAll(array $filters = [])
    {
        return $this->borrowRepository->all($filters);
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
            $previousStatus = $borrow->status;

            $copy = null;
            if ($newStatus === 'returned' && $previousStatus !== 'returned') {
                $copy = Copy::query()->lockForUpdate()->find($borrow->copy_id);
            }

            if ($newStatus === 'returned' && empty($data['return_date']) && empty($borrow->return_date)) {
                $data['return_date'] = Carbon::now();
            }

            $borrow = $this->borrowRepository->update($id, $data);

            $borrow->refresh();

            if ($copy && $copy->status === 'on_loan') {
                $copy->update(['status' => 'available']);
            }

            /*
             * Create overdue fine only when the book is actually returned.
             *
             * The scheduler only changes:
             *
             * active → overdue
             *
             * It does NOT create a fine every day.
             */
            if ($newStatus === 'returned') {
                $this->fineService->fineForBorrowOnReturn(
                    borrow: $borrow,
                    returnDate: $borrow->return_date?->toDateString(),
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
