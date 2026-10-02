<?php

namespace App\Http\Resources\v1\Central;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CentralInvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $invoice = $this->resource;

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
        ];
    }
}