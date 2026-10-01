<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CentralInvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'invoice_type' => $this->invoice_type,

            'tenant_id' => $this->tenant_id,

            'tenant' => $this->whenLoaded('tenant', fn () => [
                'id' => $this->tenant->id,

                'owner' => $this->tenant->owner_name,
                'company_name' => $this->tenant->company_name,
                'email' => $this->tenant->owner_email,
                'subdomain' => $this->tenant->tenant_code,
            ]),

            'subscription_id' => $this->subscription_id,
            'subscription_payment_id' => $this->subscription_payment_id,

            'subscription_plan_id' => $this->whenLoaded(
                'subscription',
                fn () => $this->subscription->subscription_plan_id
            ),

            'subtotal' => $this->subtotal,
            'tax' => $this->tax,
            'discount' => $this->discount,
            'total_amount' => $this->total_amount,
            'paid_amount' => $this->paid_amount,
            'remaining_amount' => $this->remaining_amount,

            'currency' => $this->currency,
            'currency_symbol' => $this->currency_symbol,

            'status' => $this->status,
            'due_date' => $this->due_date,
            'notes' => $this->notes,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
