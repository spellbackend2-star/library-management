<?php

namespace App\Services;

use App\Models\BookCategory;
use App\Repositories\Interface\BookCategoryInterface;

class BookCategoryService
{
    public function __construct(
        protected BookCategoryInterface $bookCategoryRepository
    ) {}

    public function getAll()
    {
        return $this->bookCategoryRepository->all();
    }

    public function getById(int $id): ?BookCategory
    {
        return $this->bookCategoryRepository->find($id);
    }

    public function create(array $data): BookCategory
    {
        return $this->bookCategoryRepository->create($data);
    }

    public function update(int $id, array $data): BookCategory
    {
        return $this->bookCategoryRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->bookCategoryRepository->delete($id);
    }
}
