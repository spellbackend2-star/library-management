<?php

namespace App\Services;

use App\Models\Member;
use App\Repositories\Interface\MemberInterface;

class MemberService
{
    public function __construct(
        protected MemberInterface $memberRepository
    ) {}

    public function getAll()
    {
        return $this->memberRepository->all();
    }

    public function getById(int $id): ?Member
    {
        return $this->memberRepository->find($id);
    }

    public function create(array $data): Member
    {
        return $this->memberRepository->create($data);
    }

    public function update(int $id, array $data): Member
    {
        return $this->memberRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->memberRepository->delete($id);
    }
}