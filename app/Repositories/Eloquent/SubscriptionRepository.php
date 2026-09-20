<?php

namespace App\Repositories\Eloquent;

use App\Models\Subscription;
use App\Repositories\BaseRepository;
use App\Repositories\Interface\SubscriptionInterface;

class SubscriptionRepository extends BaseRepository implements SubscriptionInterface
{
    protected array $allowedSorts = [
        'id',
        'subscription_plan_id',
        'amount',
        'starts_at',
        'expires_at',
        'status',
        'created_at',
        'updated_at',
    ];

    public function all()
    {
        return Subscription::latest()->get();
    }

    public function getAll(array $filters = [])
    {
        $query = Subscription::query();

        if (isset($filters['search']) && $filters['search'] !== '') {
            $query->where(function ($q) use ($filters) {
                $q->where('status', 'like', '%'.$filters['search'].'%');
            });
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['subscription_plan_id']) && $filters['subscription_plan_id'] !== '') {
            $query->where('subscription_plan_id', $filters['subscription_plan_id']);
        }

        if (isset($filters['tenant_id']) && $filters['tenant_id'] !== '') {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        if (isset($filters['min_amount']) && $filters['min_amount'] !== '') {
            $query->where('amount', '>=', (float) $filters['min_amount']);
        }

        if (isset($filters['max_amount']) && $filters['max_amount'] !== '') {
            $query->where('amount', '<=', (float) $filters['max_amount']);
        }

        if (! isset($filters['sort_by']) || ! in_array($filters['sort_by'], $this->allowedSorts, true)) {
            $query->latest('id');
        }

        return $this->getPaginated($query, $filters);
    }

    public function find(int $id): ?Subscription
    {
        return Subscription::with(['plan', 'payments'])->find($id);
    }

    public function create(array $data): Subscription
    {
        return Subscription::create($data);
    }

    public function update(int $id, array $data): Subscription
    {
        $subscription = Subscription::findOrFail($id);

        $subscription->update($data);

        return $subscription->fresh(['plan', 'payments']);
    }

    public function delete(int $id): bool
    {
        return Subscription::findOrFail($id)->delete();
    }
}
