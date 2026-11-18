<?php

namespace App\Services;

use App\Models\Member;
use App\Models\Package;
use App\Repositories\Interface\MemberInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class MemberService
{
    public function __construct(
        protected MemberInterface $memberRepository
    ) {}

    public function getAll(array $filters = [])
    {
        return $this->memberRepository->getAll($filters);
    }

    public function getAllWithoutPagination()
    {
        return $this->memberRepository->all();
    }

    public function getById(int $id): ?Member
    {
        return $this->memberRepository->find($id);
    }

    public function create(array $data): Member
    {
        $data = $this->calculateMembershipExpiry($data);

        return $this->memberRepository->create($data);
    }

    public function update(int $id, array $data): Member
    {
        $existing = $this->memberRepository->find($id);

        if (! $existing) {
            throw (new ModelNotFoundException)->setModel(Member::class, [$id]);
        }

        if (
            ($data['status'] ?? null) === 'active'
            && $existing->status !== 'active'
        ) {
            $this->ensurePaidMembershipDaysRemain($existing);
        }

        if (
            ! empty($data['package_id'])
            && $data['package_id'] !== (string) $existing->package_id
        ) {
            unset($data['membership_expiry']);
            unset($data['membership_start']);
        }

        $data = $this->calculateMembershipExpiry($data, $existing);

        return $this->memberRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->memberRepository->delete($id);
    }

    public function suspend(int $id): Member
    {
        return $this->memberRepository->update($id, ['status' => 'suspended']);
    }

    public function activate(int $id): Member
    {
        $member = $this->memberRepository->find($id);

        if (! $member) {
            throw (new ModelNotFoundException)->setModel(Member::class, [$id]);
        }

        $this->ensurePaidMembershipDaysRemain($member);

        return $this->memberRepository->update($id, ['status' => 'active']);
    }

    private function ensurePaidMembershipDaysRemain(Member $member): void
    {
        if (
            ! $member->paid_through
            || $member->paid_through->lt(Carbon::today())
            || ! $member->membership_expiry
            || $member->membership_expiry->lt(Carbon::today())
        ) {
            throw ValidationException::withMessages([
                'status' => ['The member has no paid membership days remaining. Record a payment before activating them.'],
            ]);
        }
    }

    protected function calculateMembershipExpiry(array $data, ?Member $existing = null): array
    {
        $packageId = $data['package_id'] ?? $existing?->package_id;

        if (! $packageId) {
            return $data;
        }

        $package = Package::find($packageId);

        if (! $package) {
            return $data;
        }

        if (! array_key_exists('membership_start', $data) || $data['membership_start'] === null) {
            $data['membership_start'] = $existing
                ? $existing->membership_start?->format('Y-m-d')
                : Carbon::today()->toDateString();
        }

        $start = Carbon::parse($data['membership_start']);

        $data['membership_expiry'] = match ($package->duration_unit) {
            'day' => $start->copy()->addDays($package->duration)->toDateString(),
            'month' => $start->copy()->addMonths($package->duration)->toDateString(),
            'year' => $start->copy()->addYears($package->duration)->toDateString(),
            default => $start->copy()->addMonths($package->duration)->toDateString(),
        };

        return $data;
    }
}
