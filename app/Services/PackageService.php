<?php

namespace App\Services;

use App\Models\Package;
use App\Repositories\Interface\PackageInterface;

class PackageService
{
    public function __construct(
        protected PackageInterface $packageRepository
    ) {}

    public function getAll(array $filters = [])
    {
        return $this->packageRepository->getAll($filters);
    }

    public function getById(int $id): ?Package
    {
        return $this->packageRepository->find($id);
    }

    public function create(array $data): Package
    {
        $data = $this->normalizeAccessFields($data);

        return $this->packageRepository->create($data);
    }

    public function update(int $id, array $data): Package
    {
        $data = $this->normalizeAccessFields($data);

        return $this->packageRepository->update($id, $data);
    }

    private function normalizeAccessFields(array $data): array
    {
        if (array_key_exists('seat_access_allowed', $data) && $data['seat_access_allowed'] == false) {
            $data['max_seat_hours_per_day'] = null;
        }

        if (array_key_exists('locker_allowed', $data) && $data['locker_allowed'] == false) {
            $data['locker_type'] = null;
            $data['max_locker_hours_per_day'] = null;
        }

        return $data;
    }

    public function delete(int $id): bool
    {
        return $this->packageRepository->delete($id);
    }
}
