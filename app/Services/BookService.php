<?php

namespace App\Services;

use App\Models\Book;
use App\Repositories\Interface\BookInterface;

class BookService
{
    public function __construct(
        protected BookInterface $bookRepository
    ) {}

    public function getAll()
    {
        return $this->bookRepository->all();
    }

    public function getById(int $id): ?Book
    {
        return $this->bookRepository->find($id);
    }

    public function create(array $data): Book
    {
        return $this->bookRepository->create($data);
    }

    public function update(int $id, array $data): Book
    {
        return $this->bookRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->bookRepository->delete($id);
    }
}
