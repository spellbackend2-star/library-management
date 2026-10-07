<?php

namespace App\Repositories\Eloquent;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Repositories\BaseRepository;
use App\Repositories\Interface\SubscriptionInterface;
use Carbon\Carbon;

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
                $q->where('status', 'like', '%' . $filters['search'] . '%');
            });
        }
        // Tenant status filter
        if (isset($filters['tenant_status']) && $filters['tenant_status'] !== '') {
            $query->whereHas('tenant', function ($q) use ($filters) {
                $q->where('status', $filters['tenant_status']);
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

    public function updateStatus(int $id, string $status): Subscription
    {
        $subscription = Subscription::lockForUpdate()->findOrFail($id);

        $current = $subscription->status;

        $allowed = [
            'pending' => ['active', 'cancelled'],
            'active' => ['expired', 'cancelled'],
            'expired' => ['active'],
        ];

        if (! isset($allowed[$current]) || ! in_array($status, $allowed[$current], true)) {
            throw new \InvalidArgumentException(
                "Invalid status transition from {$current} to {$status}."
            );
        }

        $subscription->update(['status' => $status]);

        return $subscription->fresh(['plan', 'payments']);
    }

    public function cancel(int $id): Subscription
    {
        return $this->updateStatus($id, 'cancelled');
    }

    public function changePlan(int $id, int $planId): Subscription
    {
        $subscription = Subscription::lockForUpdate()->findOrFail($id);

        if ($subscription->status === 'cancelled') {
            throw new \InvalidArgumentException(
                'Cannot change plan for a cancelled subscription.'
            );
        }

        $plan = SubscriptionPlan::findOrFail($planId);

        $startDate = now();
        $expiresAt = match (strtolower($plan->duration_unit ?? 'month')) {
            'day' => $startDate->copy()->addDays((int) $plan->duration),
            'month' => $startDate->copy()->addMonths((int) $plan->duration),
            'year' => $startDate->copy()->addYears((int) $plan->duration),
            default => $startDate->copy()->addMonths((int) $plan->duration),
        };

        if (
            in_array($subscription->status, ['active', 'pending'], true)
            && $subscription->expires_at
            && $subscription->expires_at >= $startDate->toDateString()
        ) {
            $startDate = Carbon::parse($subscription->expires_at);
            $expiresAt = match (strtolower($plan->duration_unit ?? 'month')) {
                'day' => $startDate->copy()->addDays((int) $plan->duration),
                'month' => $startDate->copy()->addMonths((int) $plan->duration),
                'year' => $startDate->copy()->addYears((int) $plan->duration),
                default => $startDate->copy()->addMonths((int) $plan->duration),
            };
        }

        $subscription->update([
            'subscription_plan_id' => $planId,
            'starts_at' => $startDate->toDateString(),
            'expires_at' => $expiresAt->toDateString(),
            'status' => 'active',
        ]);

        return $subscription->fresh(['plan', 'payments']);
    }

    public function delete(int $id): bool
    {
        return Subscription::findOrFail($id)->delete();
    }
}
