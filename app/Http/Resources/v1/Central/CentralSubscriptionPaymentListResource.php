<?php

namespace App\Http\Resources\v1\Central;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CentralSubscriptionPaymentListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $payment = $this->resource;
        $tenant = $payment->tenant;
        $subscription = $payment->subscription;
        $plan = $subscription?->plan;
        $invoice = $payment->invoice;

        return [
            'id' => $payment->id,
            'transaction_id' => $payment->transaction_id,
            'amount' => $payment->amount,
            'currency' => $invoice?->currency ?? 'NPR',
            'payment_method' => $payment->payment_method,
            'status' => $payment->status,
            'paid_at' => $payment->paid_at
                ? Carbon::parse($payment->paid_at)->format('Y-m-d\TH:i:s\Z')
                : null,
            'created_at' => $payment->created_at
                ? Carbon::parse($payment->created_at)->format('Y-m-d\TH:i:s\Z')
                : null,
            'tenant' => $tenant
                ? [
                    'id' => $tenant->id,
                    'company_name' => $tenant->company_name,
                    'phone' => $tenant->phone,
                    'tenant_code' => $tenant->tenant_code,
                ]
                : null,
            'plan' => $plan
                ? [
                    'id' => $plan->id,
                    'name' => $plan->name,
                ]
                : null,
            'subscription' => $subscription
                ? [
                    'id' => $subscription->id,
                    'status' => $subscription->status,
                    'starts_at' => $subscription->starts_at ? (string) $subscription->starts_at : null,
                    'expires_at' => $subscription->expires_at ? (string) $subscription->expires_at : null,
                ]
                : null,
            'invoice' => $invoice
                ? [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'status' => $invoice->status,
                ]
                : null,
        ];
    }
}
