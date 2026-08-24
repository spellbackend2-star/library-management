<?php

namespace App\Repositories\Interface;

use App\Models\Member;

interface MemberInterface
{
    public function all();

    public function find(int $id): ?Member;

    public function create(array $data): Member;

    public function update(int $id, array $data): Member;

    public function delete(int $id): bool;
}