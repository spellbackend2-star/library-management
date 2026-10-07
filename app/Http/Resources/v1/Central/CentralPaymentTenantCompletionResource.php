<?php

namespace App\Http\Resources\v1\Central;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CentralPaymentTenantCompletionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $payment = $this->resource['subscription_payment'];
        $invoice = $this->resource['invoice'];
        $tenant = $this->resource['tenant'];
        $subscription = $this->resource['subscription'];
        $plan = $subscription->plan;

        return [
            'payment' => [
                'id' => $payment->id,
                'amount' => $payment->amount,
                'payment_method' => $payment->payment_method,
                'status' => $payment->status,
                'transaction_id' => $payment->transaction_id,
                'paid_at' => $payment->paid_at,
            ],
            'invoice' => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'subtotal' => $invoice->subtotal,
                'coupon_id' => $invoice->coupon_id,
                'coupon_discount' => $invoice->coupon_discount,
                'total_amount' => $invoice->total_amount,
                'paid_amount' => $invoice->paid_amount,
                'remaining_amount' => $invoice->remaining_amount,
                'status' => $invoice->status,
            ],
            'tenant' => [
                'id' => $tenant->id,
                'company_name' => $tenant->company_name,
                'phone' => $tenant->phone,
                'tenant_code' => $tenant->tenant_code,
                'owner_name' => $tenant->owner_name,
                'owner_email' => $tenant->owner_email,
                'status' => $tenant->status,
            ],
            'domain' => $this->resource['domain'],
            'subscription' => [
                'id' => $subscription->id,
                'plan_id' => $plan->id,
                'plan_name' => $plan->name,
                'amount' => $subscription->amount,
                'starts_at' => $subscription->starts_at,
                'expires_at' => $subscription->expires_at,
                'status' => $subscription->status,
            ],
        ];
    }
}
