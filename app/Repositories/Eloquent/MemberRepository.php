<?php

namespace App\Repositories\Eloquent;

use App\Models\Member;
use App\Repositories\BaseRepository;
use App\Repositories\Interface\MemberInterface;

class MemberRepository extends BaseRepository implements MemberInterface
{
    protected array $allowedSorts = [
        'id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'package_id',
        'status',
        'gender',
        'membership_start',
        'membership_expiry',
        'created_at',
        'updated_at',
    ];

    public function all()
    {
        return Member::with('package')->latest()->get();
    }

    public function getAll(array $filters = [])
    {
        $query = Member::query()->with('package');

        if (isset($filters['search']) && $filters['search'] !== '') {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', '%'. $search .'%')
                    ->orWhere('last_name', 'like', '%'. $search .'%')
                    ->orWhere('email', 'like', '%'. $search .'%')
                    ->orWhere('phone', 'like', '%'. $search .'%');

                if (is_numeric($search)) {
                    $q->orWhere('id', $search);
                }
            });
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if (
            array_key_exists('package_id', $filters)
            && $filters['package_id'] !== null
            && $filters['package_id'] !== ''
        ) {
            $query->where('package_id', $filters['package_id']);
        }

        if (isset($filters['gender']) && $filters['gender'] !== '') {
            $query->where('gender', $filters['gender']);
        }

        $query = $this->applySorting($query, $filters);

        $paginator = $this->applyPagination($query, $filters);

        return [
            'data' => $paginator->items(),
            'meta' => $this->paginationMeta($paginator),
        ];
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
