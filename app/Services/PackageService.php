<?php

namespace App\Services;

use App\Models\Package;
use App\Repositories\Interface\PackageInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PackageService
{
    private const MEMBERSHIP_RULE_FIELDS = [
        'price',
        'duration',
        'duration_unit',
        'max_book_loans',
        'max_borrow_days',
        'seat_access_allowed',
        'max_seat_hours_per_day',
        'locker_allowed',
        'locker_type',
        'max_locker_hours_per_day',
        'overdue_fine_per_day',
        'seat_overdue_fine_per_hour',
        'locker_overdue_fine_per_day',
    ];

    private const INTEGER_RULE_FIELDS = [
        'duration',
        'max_book_loans',
        'max_borrow_days',
        'max_seat_hours_per_day',
        'max_locker_hours_per_day',
    ];

    private const DECIMAL_RULE_FIELDS = [
        'price',
        'overdue_fine_per_day',
        'seat_overdue_fine_per_hour',
        'locker_overdue_fine_per_day',
    ];

    private const BOOLEAN_RULE_FIELDS = [
        'seat_access_allowed',
        'locker_allowed',
    ];

    public function __construct(
        protected PackageInterface $packageRepository
    ) {}

    public function getAll(array $filters = [])
    {
        return $this->packageRepository->getAll($filters);
    }

    public function getAllWithoutPagination()
    {
        return $this->packageRepository->all();
    }

    public function getById(int $id): ?Package
    {
        return $this->packageRepository->find($id);
    }

    public function create(array $data): Package
    {
        $data = $this->normalizeAccessFields($data);

        return $this->packageRepository->create($data);
    }

    public function update(int $id, array $data): Package
    {
        $data = $this->normalizeAccessFields($data);

        return DB::transaction(function () use ($id, $data): Package {
            $package = Package::query()->lockForUpdate()->findOrFail($id);

            if ($data === []) {
                return $package;
            }

            $hasCurrentMembers = $package->members()
                ->where(function ($query): void {
                    $query->whereNull('membership_expiry')
                        ->orWhereDate('membership_expiry', '>=', Carbon::today());
                })
                ->exists();

            $hasMembershipRuleChanges = $this->hasMembershipRuleChanges($package, $data);

            if ($package->retired_at) {
                if (
                    ! $hasCurrentMembers
                    || $hasMembershipRuleChanges
                    || $this->hasActiveStatusChange($package, $data)
                ) {
                    throw ValidationException::withMessages([
                        'package' => ['A retired package cannot be reactivated or have its membership rules changed.'],
                    ]);
                }

                $descriptiveData = array_intersect_key(
                    $data,
                    array_flip(['name', 'description'])
                );

                return $descriptiveData === []
                    ? $package
                    : $this->packageRepository->update($id, $descriptiveData);
            }

            $hasPackageHistory = $package->members()->exists()
                || $package->bookings()->exists();

            if (! $hasPackageHistory) {
                return $this->packageRepository->update($id, $data);
            }

            if (
                $hasMembershipRuleChanges
                || $this->hasActiveStatusChange($package, $data)
            ) {
                throw ValidationException::withMessages([
                    'package' => ['Only the name and description can be changed after members or bookings have used this package. Create a new package for different membership rules.'],
                ]);
            }

            $descriptiveData = array_intersect_key(
                $data,
                array_flip(['name', 'description'])
            );

            return $descriptiveData === []
                ? $package
                : $this->packageRepository->update($id, $descriptiveData);
        });
    }

    private function normalizeAccessFields(array $data): array
    {
        if (array_key_exists('seat_access_allowed', $data) && $data['seat_access_allowed'] == false) {
            $data['max_seat_hours_per_day'] = null;
        }

        if (array_key_exists('locker_allowed', $data) && $data['locker_allowed'] == false) {
            $data['locker_type'] = null;
            $data['max_locker_hours_per_day'] = null;
        }

        return $data;
    }

    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id): bool {
            $package = Package::query()->lockForUpdate()->findOrFail($id);
            $hasCurrentMembers = $package->members()
                ->where(function ($query): void {
                    $query->whereNull('membership_expiry')
                        ->orWhereDate('membership_expiry', '>=', Carbon::today());
                })
                ->exists();

            if ($hasCurrentMembers) {
                $package->update([
                    'is_active' => false,
                    'retired_at' => $package->retired_at ?? Carbon::today(),
                ]);

                return false;
            }

            return $this->packageRepository->delete($id);
        });
    }

    private function hasMembershipRuleChanges(Package $package, array $data): bool
    {
        foreach (self::MEMBERSHIP_RULE_FIELDS as $field) {
            if (
                array_key_exists($field, $data)
                && $this->normalizeRuleValue($field, $data[$field])
                    !== $this->normalizeRuleValue($field, $package->getAttribute($field))
            ) {
                return true;
            }
        }

        return false;
    }

    private function hasActiveStatusChange(Package $package, array $data): bool
    {
        return array_key_exists('is_active', $data)
            && $data['is_active'] !== null
            && filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN) !== (bool) $package->is_active;
    }

    private function normalizeRuleValue(string $field, mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (in_array($field, self::INTEGER_RULE_FIELDS, true)) {
            return (int) $value;
        }

        if (in_array($field, self::DECIMAL_RULE_FIELDS, true)) {
            return number_format((float) $value, 2, '.', '');
        }

        if (in_array($field, self::BOOLEAN_RULE_FIELDS, true)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        return (string) $value;
    }
}
