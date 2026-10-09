<?php

namespace App\Http\Resources\v1\Central;

use App\Models\SubscriptionPayment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CentralSubscriptionPaymentSummaryResource extends JsonResource
{
    public function __construct(
        $resource,
        protected ?array $gateway = null,
        protected ?string $returnUrl = null
    ) {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        /** @var SubscriptionPayment $payment */
        $payment = $this->resource;

        $payment->loadMissing([
            'subscription.plan',
            'tenant.domains',
            'invoice',
        ]);

        $tenant = $payment->tenant;

        $isPaid = in_array(
            $payment->status,
            ['SUCCESS', 'COMPLETED'],
            true
        );

        $isTenantActive =
            $tenant &&
            $tenant->status === 'active';

        $domain =
            $tenant?->domains?->first()?->domain;

        $summary = [
            'payment' => [
                'id' => $payment->id,
                'amount' => $payment->amount,
                'payment_method' => $payment->payment_method,
                'status' => $payment->status,
                'transaction_id' => $payment->transaction_id,
                'payment_url' => $payment->payment_url,
                'paid_at' => $payment->paid_at,
            ],

            'invoice' => $payment->invoice
                ? [
                    'id' => $payment->invoice->id,
                    'invoice_number' => $payment->invoice->invoice_number,
                    'coupon_id' => $payment->invoice->coupon_id,
                    'coupon_discount' => $payment->invoice->coupon_discount,
                    'total_amount' => $payment->invoice->total_amount,
                    'paid_amount' => $payment->invoice->paid_amount,
                    'remaining_amount' => $payment->invoice->remaining_amount,
                    'status' => $payment->invoice->status,
                    'due_date' => $payment->invoice->due_date,
                ]
                : null,

            'payments' => $payment->invoice
                ? $payment->invoice->payments()
                    ->orderBy('id')
                    ->get([
                        'id',
                        'invoice_id',
                        'subscription_id',
                        'amount',
                        'payment_method',
                        'status',
                        'transaction_id',
                        'paid_at',
                    ])
                    ->map(fn (SubscriptionPayment $invoicePayment): array => [
                        'id' => $invoicePayment->id,
                        'invoice_id' => $invoicePayment->invoice_id,
                        'subscription_id' => $invoicePayment->subscription_id,
                        'amount' => $invoicePayment->amount,
                        'payment_method' => $invoicePayment->payment_method,
                        'status' => $invoicePayment->status,
                        'transaction_id' => $invoicePayment->transaction_id,
                        'paid_at' => $invoicePayment->paid_at,
                    ])
                    ->all()
                : [],

            'subscription' => $payment->subscription
                ? [
                    'id' => $payment->subscription->id,
                    'status' => $payment->subscription->status,
                    'starts_at' => $payment->subscription->starts_at,
                    'expires_at' => $payment->subscription->expires_at,
                    'plan' => $payment->subscription->plan
                        ? [
                            'id' => $payment->subscription->plan->id,
                            'name' => $payment->subscription->plan->name,
                            'price' => $payment->subscription->plan->price,
                            'duration' => $payment->subscription->plan->duration,
                            'duration_unit' => $payment->subscription->plan->duration_unit,
                        ]
                        : null,
                ]
                : null,

            'tenant' => $tenant
                ? [
                    'id' => $tenant->id,
                    'company_name' => $tenant->company_name,
                    'phone' => $tenant->phone,
                    'tenant_code' => $tenant->tenant_code,
                    'status' => $tenant->status,
                    'domain' => $domain,
                ]
                : null,

            'is_paid' => $isPaid,

            'is_tenant_active' => $isTenantActive,
        ];

        if ($this->gateway) {
            $summary['gateway'] = $this->gateway;
        }

        if ($this->returnUrl !== null) {
            $summary['payment']['return_url'] = $this->returnUrl;
        }

        if ($isTenantActive) {
            $summary['next_step'] = 'Payment successful. The tenant is active and can now log in.';

            $summary['login'] = [
                'url' => $domain
                    ? 'https://' . $domain . '/login'
                    : null,
                'method' => 'POST',
            ];

            if ((float) $payment->invoice?->remaining_amount > 0) {
                $summary['pay'] = [
                    'method' => 'POST',
                    'url' => route(
                        'central.subscription-payments.pay',
                        ['payment' => $payment->id]
                    ),
                    'allowed_payment_methods' => [
                        'CASH',
                        'KHALTI',
                        'ESEWA',
                    ],
                ];

                $summary['status_url'] = route(
                    'central.subscription-payments.status',
                    ['payment' => $payment->id]
                );
            }
        } else {
            $remainingAmount = (float) $payment->invoice?->remaining_amount;
            $summary['next_step'] = $remainingAmount > 0
                ? 'Pay the remaining invoice balance to complete the subscription payment.'
                : ($tenant?->status === 'pending'
                    ? 'Invoice paid. Tenant registration is pending central admin review.'
                    : 'Invoice paid. Complete tenant registration using this payment.');

            if ($remainingAmount > 0) {
                $summary['pay'] = $payment->tenant_id !== null
                    ? [
                        'method' => 'POST',
                        'url' => route(
                            'central.subscription-payments.pay',
                            ['payment' => $payment->id]
                        ),
                        'allowed_payment_methods' => ['CASH', 'KHALTI', 'ESEWA'],
                    ]
                    : [
                        'method' => 'POST',
                        'url' => route('central.subscription-payments.pay-invoice'),
                        'invoice_id' => $payment->invoice?->id,
                        'amount' => $payment->invoice?->remaining_amount,
                        'allowed_payment_methods' => ['KHALTI', 'ESEWA'],
                    ];
            } elseif (! $tenant) {
                $summary['tenant_registration'] = [
                    'method' => 'POST',
                    'url' => route(
                        'central.subscription-payments.complete-and-create-tenant',
                        ['payment' => $payment->id]
                    ),
                ];
            }

            $summary['status_url'] = route(
                'central.subscription-payments.status',
                [
                    'payment' => $payment->id,
                ]
            );
        }

        return $summary;
    }
}
