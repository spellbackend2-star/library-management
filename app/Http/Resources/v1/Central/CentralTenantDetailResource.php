<?php

namespace App\Http\Resources\v1\Central;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CentralTenantDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $tenant = $this->resource['tenant'];
        $subscription = $this->resource['subscription'];
        $payments = $this->resource['payments'];
        $invoices = $this->resource['invoices'];

        return [
            'profile' => [
                'id' => $tenant->id,
                'company_name' => $tenant->company_name,
                'phone' => $tenant->phone,
                'tenant_code' => $tenant->tenant_code,
                'owner_email' => $tenant->owner_email,
                'owner_name' => $tenant->owner_name,
                'status' => $tenant->status,
                'suspension_reason' => $tenant->suspension_reason,
                'domain' => $tenant->domains->first()?->domain,
                'created_at' => $tenant->created_at,
            ],

            'current_subscription' => $subscription
                ? [
                    'id' => $subscription->id,
                    'status' => $subscription->status,
                    'starts_at' => $subscription->starts_at,
                    'expires_at' => $subscription->expires_at,

                    'plan' => $subscription->plan
                        ? [
                            'id' => $subscription->plan->id,
                            'name' => $subscription->plan->name,
                            'price' => $subscription->plan->price,
                            'duration' => $subscription->plan->duration,
                            'duration_unit' => $subscription->plan->duration_unit,
                        ]
                        : null,
                ]
                : null,

            'payments' => $payments->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'amount' => $payment->amount,
                    'payment_method' => $payment->payment_method,
                    'status' => $payment->status,
                    'transaction_id' => $payment->transaction_id,
                    'paid_at' => $payment->paid_at,

                    'subscription' => $payment->subscription
                        ? [
                            'id' => $payment->subscription->id,
                            'status' => $payment->subscription->status,

                            'plan' => $payment->subscription->plan
                                ? [
                                    'id' => $payment->subscription->plan->id,
                                    'name' => $payment->subscription->plan->name,
                                    'price' => $payment->subscription->plan->price,
                                ]
                                : null,
                        ]
                        : null,

                    'invoice' => $payment->invoice
                        ? [
                            'id' => $payment->invoice->id,
                            'invoice_number' => $payment->invoice->invoice_number,
                            'total_amount' => $payment->invoice->total_amount,
                            'paid_amount' => $payment->invoice->paid_amount,
                            'remaining_amount' => $payment->invoice->remaining_amount,
                            'status' => $payment->invoice->status,
                            'due_date' => $payment->invoice->due_date,
                        ]
                        : null,
                ];
            }),

            'invoices' => [
                'data' => collect($invoices['data'])->map(function ($invoice) {
                    return [
                        'id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'invoice_type' => $invoice->invoice_type,
                        'subtotal' => $invoice->subtotal,
                        'tax' => $invoice->tax,
                        'discount' => $invoice->discount,
                        'total_amount' => $invoice->total_amount,
                        'paid_amount' => $invoice->paid_amount,
                        'remaining_amount' => $invoice->remaining_amount,
                        'currency' => $invoice->currency,
                        'currency_symbol' => $invoice->currency_symbol,
                        'status' => $invoice->status,
                        'due_date' => $invoice->due_date,
                        'notes' => $invoice->notes,

                        'subscription' => $invoice->subscription
                            ? [
                                'id' => $invoice->subscription->id,
                                'status' => $invoice->subscription->status,

                                'plan' => $invoice->subscription->plan
                                    ? [
                                        'id' => $invoice->subscription->plan->id,
                                        'name' => $invoice->subscription->plan->name,
                                        'price' => $invoice->subscription->plan->price,
                                    ]
                                    : null,
                            ]
                            : null,

                        'created_at' => $invoice->created_at,
                    ];
                }),

                'pagination' => $invoices['meta'],
            ],
        ];
    }
}