<?php

namespace App\Services;

use App\Models\Author;
use App\Repositories\Interface\AuthorInterface;

class AuthorService
{
    public function __construct(
        protected AuthorInterface $authorRepository
    ) {}

    public function getAll()
    {
        return $this->authorRepository->all();
    }

    public function getById(int $id): ?Author
    {
        return $this->authorRepository->find($id);
    }

    public function create(array $data): Author
    {
        return $this->authorRepository->create($data);
    }

    public function update(int $id, array $data): Author
    {
        return $this->authorRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->authorRepository->delete($id);
    }
}
