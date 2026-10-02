<?php

namespace App\Http\Resources\v1\Central;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CentralPaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $payment = $this->resource;

        $resource = [
            'id' => $payment->id,
            'invoice_id' => $payment->invoice_id,
            'subscription_id' => $payment->subscription_id,
            'amount' => $payment->amount,
            'payment_method' => $payment->payment_method,
            'status' => $payment->status,
            'transaction_id' => $payment->transaction_id,
            'paid_at' => $payment->paid_at,
        ];

        if (array_key_exists('return_url', $payment->getAttributes())) {
            $resource['return_url'] = $payment->getAttribute('return_url');
        }

        return $resource;
    }
}