<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'subscription_plan_id' => $this->subscription_plan_id,
            'amount' => $this->amount,
            'starts_at' => $this->starts_at,
            'expires_at' => $this->expires_at,
            'status' => $this->status,
            'tenant' => $this->whenLoaded('tenant', function () {
                return $this->tenant ? [
                    'id' => $this->tenant->id,
                    'company_name' => $this->tenant->company_name,
                    'tenant_code' => $this->tenant->tenant_code,
                    'owner_email' => $this->tenant->owner_email,
                    'status' => $this->tenant->status,
                ] : null;
            }),
            'plan' => $this->whenLoaded('plan', fn () => new SubscriptionPlanResource($this->plan)),
            'payments' => $this->whenLoaded('payments', fn () => $this->payments),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
