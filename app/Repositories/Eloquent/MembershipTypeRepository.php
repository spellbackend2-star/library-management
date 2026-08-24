<?php

namespace App\Repositories\Eloquent;

use App\Models\MembershipType;
use App\Repositories\Interface\MembershipTypeInterface;
use Illuminate\Database\Eloquent\Collection;

class MembershipTypeRepository implements MembershipTypeInterface
{
    public function all(): Collection
    {
        return MembershipType::query()
            ->latest('id')
            ->get();
    }

    public function find(int $id): ?MembershipType
    {
        return MembershipType::find($id);
    }

    public function create(array $data): MembershipType
    {
        return MembershipType::create($data);
    }

    public function update(int $id, array $data): ?MembershipType
    {
        $membershipType = MembershipType::find($id);

        if (!$membershipType) {
            return null;
        }

        $membershipType->update($data);

        return $membershipType->fresh();
    }

    public function delete(int $id): bool
    {
        $membershipType = MembershipType::find($id);

        if (!$membershipType) {
            return false;
        }

        return (bool) $membershipType->delete();
    }
}