<?php

namespace App\Services;

use App\Models\BookEdition;
use App\Repositories\Interface\BookEditionInterface;

class BookEditionService
{
    public function __construct(
        protected BookEditionInterface $bookEditionRepository
    ) {}

    public function getAll(array $filters = []): array
    {
        return $this->bookEditionRepository->getAll($filters);
    }

    public function getById(int $id): ?BookEdition
    {
        return $this->bookEditionRepository->find($id);
    }

    public function create(array $data): BookEdition
    {
        return $this->bookEditionRepository->create($data);
    }

    public function update(int $id, array $data): BookEdition
    {
        return $this->bookEditionRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->bookEditionRepository->delete($id);
    }
}
