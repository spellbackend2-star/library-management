<?php

namespace App\Repositories\Eloquent;

use App\Models\Member;
use App\Repositories\Interface\MemberInterface;

class MemberRepository implements MemberInterface
{
    public function all()
    {
        return Member::with('package')->latest()->get();
    }

    public function find(int $id): ?Member
    {
        return Member::with('package')->find($id);
    }

    public function create(array $data): Member
    {
        return Member::create($data);
    }

    public function update(int $id, array $data): Member
    {
        $member = Member::findOrFail($id);

        $member->update($data);

        return $member->fresh('package');
    }

    public function delete(int $id): bool
    {
        return Member::findOrFail($id)->delete();
    }
}