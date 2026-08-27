<?php

namespace App\Services;

use App\Models\Member;
use App\Models\Package;
use App\Repositories\Interface\MemberInterface;
use Illuminate\Support\Carbon;

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
        $data = $this->calculateMembershipExpiry($data);

        return $this->memberRepository->create($data);
    }

    public function update(int $id, array $data): Member
    {
        $existing = $this->memberRepository->find($id);

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