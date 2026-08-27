<?php

namespace App\Services;

use App\Models\Package;
use App\Repositories\Interface\PackageInterface;

class PackageService
{
    public function __construct(
        protected PackageInterface $packageRepository
    ) {}

    public function getAll()
    {
        return $this->packageRepository->all();
    }

    public function getById(int $id): ?Package
    {
        return $this->packageRepository->find($id);
    }

    public function create(array $data): Package
    {
        return $this->packageRepository->create($data);
    }

    public function update(int $id, array $data): Package
    {
        return $this->packageRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->packageRepository->delete($id);
    }
}
