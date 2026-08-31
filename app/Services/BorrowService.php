<?php

namespace App\Services;

use App\Models\Borrow;
use App\Repositories\Interface\BorrowInterface;

class BorrowService
{
    public function __construct(
        protected BorrowInterface $borrowRepository
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
        return $this->borrowRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->borrowRepository->delete($id);
    }
}
