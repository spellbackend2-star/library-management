<?php

namespace App\Services;

use App\Models\Copy;
use App\Repositories\Interface\CopyInterface;

class CopyService
{
    public function __construct(
        protected CopyInterface $copyRepository
    ) {}

    public function getAll()
    {
        return $this->copyRepository->all();
    }

    public function getById(int $id): ?Copy
    {
        return $this->copyRepository->find($id);
    }

    public function create(array $data): Copy
    {
        return $this->copyRepository->create($data);
    }

    public function update(int $id, array $data): Copy
    {
        return $this->copyRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->copyRepository->delete($id);
    }
}
