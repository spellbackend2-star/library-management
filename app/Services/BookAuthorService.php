<?php

namespace App\Services;

use App\Models\BookAuthor;
use App\Repositories\Interface\BookAuthorInterface;

class BookAuthorService
{
    public function __construct(
        protected BookAuthorInterface $bookAuthorRepository
    ) {}

    public function getAll()
    {
        return $this->bookAuthorRepository->all();
    }

    public function getById(int $id): ?BookAuthor
    {
        return $this->bookAuthorRepository->find($id);
    }

    public function create(array $data): BookAuthor
    {
        return $this->bookAuthorRepository->create($data);
    }

    public function update(int $id, array $data): BookAuthor
    {
        return $this->bookAuthorRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->bookAuthorRepository->delete($id);
    }
}
