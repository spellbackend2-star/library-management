<?php

namespace App\Http\Resources\v1\Central;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CentralSubscriptionPaymentInitiateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $gatewayKey = isset($this->resource['khalti']) ? 'khalti' : 'esewa';

        return [
            'subscription' => $this->resource['subscription'],
            'invoice' => $this->resource['invoice'],
            'subscription_payment' => $this->resource['subscription_payment'],
            $gatewayKey => $this->resource[$gatewayKey],
        ];
    }
}
