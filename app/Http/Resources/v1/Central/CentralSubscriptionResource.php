<?php

namespace App\Http\Resources\v1\Central;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CentralSubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $subscription = $this->resource;
        $plan = $subscription->plan;

        return [
            'id' => $subscription->id,
            'subscription_plan_id' => $subscription->subscription_plan_id,
            'amount' => $subscription->amount,
            'status' => $subscription->status,
            'starts_at' => $subscription->starts_at,
            'expires_at' => $subscription->expires_at,
            'plan' => [
                'id' => $plan->id,
                'name' => $plan->name,
                'price' => $plan->price,
                'duration' => $plan->duration,
                'duration_unit' => $plan->duration_unit,
            ],
        ];
    }
}