<?php

namespace App\Services\Central;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;

class CentralSubscriptionService
{
    public function createPendingSubscription(?Tenant $tenant, SubscriptionPlan $plan): Subscription
    {
        return Subscription::create([
            'tenant_id' => $tenant?->id,
            'subscription_plan_id' => $plan->id,
            'amount' => (float) $plan->price,
            'starts_at' => null,
            'expires_at' => null,
            'status' => 'pending',
        ]);
    }

    public function activateSubscription(
        Subscription $subscription,
        SubscriptionPlan $plan,
        bool $setDatesBeforeStatus = false
    ): Subscription {
        if ($setDatesBeforeStatus) {
            if ($subscription->status !== 'active') {
                $startDate = now();
                $expiresAt = match (strtolower($plan->duration_unit ?? 'month')) {
                    'day' => $startDate->copy()->addDays((int) $plan->duration),
                    'month' => $startDate->copy()->addMonths((int) $plan->duration),
                    'year' => $startDate->copy()->addYears((int) $plan->duration),
                    default => $startDate->copy()->addMonths((int) $plan->duration),
                };

                $subscription->update([
                    'starts_at' => $startDate->toDateString(),
                    'expires_at' => $expiresAt->toDateString(),
                ]);
            }

            $subscription->update(['status' => 'active']);

            return $subscription;
        }

        if ($subscription->status !== 'active') {
            $startDate = now();
            $expiresAt = match (strtolower($plan->duration_unit ?? 'month')) {
                'day' => $startDate->copy()->addDays((int) $plan->duration),
                'month' => $startDate->copy()->addMonths((int) $plan->duration),
                'year' => $startDate->copy()->addYears((int) $plan->duration),
                default => $startDate->copy()->addMonths((int) $plan->duration),
            };

            $subscription->update([
                'status' => 'active',
                'starts_at' => $startDate->toDateString(),
                'expires_at' => $expiresAt->toDateString(),
            ]);
        }

        return $subscription;
    }
}
