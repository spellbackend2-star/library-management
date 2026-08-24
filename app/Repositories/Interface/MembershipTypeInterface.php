<?php

namespace App\Repositories\Interface;

use App\Models\MembershipType;
use Illuminate\Database\Eloquent\Collection;

interface MembershipTypeInterface
{
    public function all(): Collection;

    public function find(int $id): ?MembershipType;

    public function create(array $data): MembershipType;

    public function update(int $id, array $data): ?MembershipType;

    public function delete(int $id): bool;
}