<?php

namespace App\Repositories\Eloquent;

use App\Models\SubscriptionPlan;
use App\Repositories\BaseRepository;
use App\Repositories\Interface\SubscriptionPlanInterface;

class SubscriptionPlanRepository extends BaseRepository implements SubscriptionPlanInterface
{
    protected array $allowedSorts = [
        'id',
        'name',
        'price',
        'duration',
        'duration_unit',
        'is_active',
        'created_at',
        'updated_at',
    ];

    public function all()
    {
        return SubscriptionPlan::latest()->get();
    }

    public function getAll(array $filters = [])
    {
        $query = SubscriptionPlan::query();

        if (
            isset($filters['search'])
            && $filters['search'] !== ''
        ) {
            $query->where(function ($q) use ($filters) {
                $q->where(
                    'name',
                    'like',
                    '%' . $filters['search'] . '%'
                )
                ->orWhere(
                    'description',
                    'like',
                    '%' . $filters['search'] . '%'
                );
            });
        }

        if (
            array_key_exists('is_active', $filters)
            && $filters['is_active'] !== null
            && $filters['is_active'] !== ''
        ) {
            $query->where(
                'is_active',
                $this->toBool($filters['is_active'])
            );
        }

        if (
            isset($filters['duration_unit'])
            && $filters['duration_unit'] !== ''
        ) {
            $query->where(
                'duration_unit',
                $filters['duration_unit']
            );
        }

        if (
            isset($filters['min_price'])
            && $filters['min_price'] !== ''
        ) {
            $query->where(
                'price',
                '>=',
                (float) $filters['min_price']
            );
        }

        if (
            isset($filters['max_price'])
            && $filters['max_price'] !== ''
        ) {
            $query->where(
                'price',
                '<=',
                (float) $filters['max_price']
            );
        }

        if (
            !isset($filters['sort_by'])
            || !in_array($filters['sort_by'], $this->allowedSorts, true)
        ) {
            $query->latest('id');
        }

        return $this->getPaginated(
            $query,
            $filters
        );
    }

    protected function toBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return filter_var(
            $value,
            FILTER_VALIDATE_BOOLEAN
        );
    }

    public function find(int $id): ?SubscriptionPlan
    {
        return SubscriptionPlan::find($id);
    }

    public function create(array $data): SubscriptionPlan
    {
        return SubscriptionPlan::create($data);
    }

    public function update(int $id, array $data): SubscriptionPlan
    {
        $subscriptionPlan = SubscriptionPlan::findOrFail($id);

        $subscriptionPlan->update($data);

        return $subscriptionPlan->fresh();
    }

    public function delete(int $id): bool
    {
        return SubscriptionPlan::findOrFail($id)->delete();
    }
}
