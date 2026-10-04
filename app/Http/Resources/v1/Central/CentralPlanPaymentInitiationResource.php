<?php

namespace App\Http\Resources\v1\Central;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CentralPlanPaymentInitiationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $subscription = $this->resource['subscription'];
        $plan = $subscription->plan;
        $invoice = $this->resource['invoice'];
        $payment = $this->resource['payment'];

        $data = [
            'subscription' => [
                'id' => $subscription->id,
                'plan_id' => $plan->id,
                'plan_name' => $plan->name,
                'amount' => $subscription->amount,
                'duration' => $plan->duration,
                'duration_unit' => $plan->duration_unit,
                'status' => $subscription->status,
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
                'due_date' => $invoice->due_date,
            ],
            'payment' => [
                'id' => $payment->id,
                'amount' => $payment->amount,
                'payment_method' => $payment->payment_method,
                'status' => $payment->status,
                'transaction_id' => $payment->transaction_id,
            ],
        ];

        if (isset($this->resource['gateway'])) {
            $data['gateway'] = $this->resource['gateway'];
        }

        return $data;
    }
}
