<?php

namespace App\Services;

use App\Models\MembershipType;
use App\Repositories\Interface\MembershipTypeInterface;
use Illuminate\Database\Eloquent\Collection;

class MembershipTypeService
{
    public function __construct(
        protected MembershipTypeInterface $membershipTypeRepository
    ) {}

    public function getAll(): Collection
    {
        return $this->membershipTypeRepository->all();
    }

    public function getById(int $id): ?MembershipType
    {
        return $this->membershipTypeRepository->find($id);
    }

    public function create(array $data): MembershipType
    {
        return $this->membershipTypeRepository->create($data);
    }

    public function update(int $id, array $data): ?MembershipType
    {
        return $this->membershipTypeRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->membershipTypeRepository->delete($id);
    }
}