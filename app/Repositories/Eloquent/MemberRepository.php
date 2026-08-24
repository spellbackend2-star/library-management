<?php

namespace App\Repositories\Eloquent;

use App\Models\Member;
use App\Repositories\Interface\MemberInterface;

class MemberRepository implements MemberInterface
{
    public function all()
    {
        return Member::with('membershipType')->latest()->get();
    }

    public function find(int $id): ?Member
    {
        return Member::with('membershipType')->find($id);
    }

    public function create(array $data): Member
    {
        return Member::create($data);
    }

    public function update(int $id, array $data): Member
    {
        $member = Member::findOrFail($id);

        $member->update($data);

        return $member->fresh('membershipType');
    }

    public function delete(int $id): bool
    {
        return Member::findOrFail($id)->delete();
    }
}