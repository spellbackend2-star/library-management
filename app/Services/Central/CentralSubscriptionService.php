<?php

namespace App\Services\Central;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Carbon\Carbon;

class CentralSubscriptionService
{
    public function createPendingSubscription(
        ?Tenant $tenant,
        SubscriptionPlan $plan,
        ?int $replacesSubscriptionId = null,
        ?Carbon $startsAt = null
    ): Subscription {
        $expiresAt = $startsAt
            ? $this->expiryDate($startsAt, $plan)
            : null;

        return Subscription::create([
            'tenant_id' => $tenant?->id,
            'subscription_plan_id' => $plan->id,
            'replaces_subscription_id' => $replacesSubscriptionId,
            'amount' => (float) $plan->price,
            'starts_at' => $startsAt?->toDateString(),
            'expires_at' => $expiresAt?->toDateString(),
            'status' => 'pending',
        ]);
    }

    public function activateSubscription(
        Subscription $subscription,
        SubscriptionPlan $plan,
        bool $setDatesBeforeStatus = false
    ): Subscription {
        if ($subscription->status !== 'active') {
            $preserveEffectiveDate = $subscription->replaces_subscription_id !== null;
            $startDate = $preserveEffectiveDate && $subscription->starts_at
                ? Carbon::parse($subscription->starts_at)
                : now();
            $expiresAt = $preserveEffectiveDate && $subscription->expires_at
                ? Carbon::parse($subscription->expires_at)
                : $this->expiryDate($startDate, $plan);

            $dates = [
                'starts_at' => $startDate->toDateString(),
                'expires_at' => $expiresAt->toDateString(),
            ];

            if (! $setDatesBeforeStatus) {
                $dates['status'] = 'active';
            }

            $subscription->update($dates);
        }

        if ($setDatesBeforeStatus) {
            $subscription->update(['status' => 'active']);
        }

        return $subscription;
    }

    private function expiryDate(Carbon $startDate, SubscriptionPlan $plan): Carbon
    {
        return match (strtolower($plan->duration_unit ?? 'month')) {
            'day' => $startDate->copy()->addDays((int) $plan->duration),
            'month' => $startDate->copy()->addMonths((int) $plan->duration),
            'year' => $startDate->copy()->addYears((int) $plan->duration),
            default => $startDate->copy()->addMonths((int) $plan->duration),
        };
    }

    public function retireReplacedSubscription(Subscription $subscription): void
    {
        if (! $subscription->replaces_subscription_id) {
            return;
        }

        Subscription::query()
            ->whereKey($subscription->replaces_subscription_id)
            ->whereIn('status', ['active', 'pending'])
            ->update(['status' => 'cancelled']);
    }
}
