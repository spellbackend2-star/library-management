<?php

namespace App\Http\Resources\v1\Central;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CentralRegistrationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $result = $this->resource;
        $payment = $result['subscription_payment'];
        $invoice = $result['invoice'];

        return [
            'status' => 'success',
            'message' => 'Tenant registered successfully',
            'tenant' => (new CentralTenantResource([
                'tenant' => $result['tenant'],
                'domain' => $result['domain'],
            ]))->resolve($request),
            'subscription' => (new CentralSubscriptionResource($result['subscription']))->resolve($request),
            'invoice' => (new CentralInvoiceResource($invoice))->resolve($request),
            'payment' => (new CentralPaymentResource($payment))->resolve($request),
            'next_step' => [
                'action' => 'payment',
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'message' => 'Complete the payment to activate the subscription and tenant.',
            ],
        ];
    }
}