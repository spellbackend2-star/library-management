<?php

namespace App\Repositories\Eloquent;

use App\Models\SubscriptionPlan;
use App\Repositories\BaseRepository;
use App\Repositories\Interface\SubscriptionPlanInterface;

class SubscriptionPlanRepository extends BaseRepository implements SubscriptionPlanInterface
{
    protected array $allowedFilters = [
        'search' => [
            'type' => 'like',
            'columns' => ['name', 'description'],
        ],
        'is_active' => [
            'type' => 'boolean',
            'column' => 'is_active',
        ],
        'duration_unit' => [
            'type' => 'exact',
            'column' => 'duration_unit',
        ],
        'min_price' => [
            'type' => 'min',
            'column' => 'price',
        ],
        'max_price' => [
            'type' => 'max',
            'column' => 'price',
        ],
    ];

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
