<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'receipt_number' => $this->receipt?->receipt_number,
            'invoice_id' => $this->invoice_id,
            'invoice_number' => $this->invoice?->invoice_number,
            'member_id' => $this->member_id,
            'staff_id' => $this->staff_id ?? $this->invoice?->staff_id,
            'member_name' => $this->member
                ? trim($this->member->first_name . ' ' . $this->member->last_name)
                : null,
            'amount' => $this->amount,
            'extra_discount' => $this->extra_discount,
            'payment_method' => $this->payment_method,
            'status' => $this->status,
            'paid_at' => $this->paid_at?->format('Y-m-d H:i:s'),
            'payment_url' => $this->payment_url,
            'return_url' => $this->return_url,
        ];
    }
}
