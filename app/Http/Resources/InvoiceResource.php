<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'member_id' => $this->member_id,
            'invoice_number' => $this->invoice_number,
            'invoice_type' => $this->invoice_type,

            'total_amount' => $this->total_amount,
            'coupon_discount' => $this->coupon_discount,
            'paid_amount' => $this->paid_amount,
            'remaining_amount' => $this->remaining_amount,
            'status' => $this->status,

            'due_date' => $this->due_date?->format('Y-m-d'),
            'notes' => $this->notes,

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),

            'member' => [
                'id' => $this->member?->id,
                'name' => trim(
                    ($this->member?->first_name ?? '') . ' ' .
                    ($this->member?->last_name ?? '')
                ),
                'email' => $this->member?->email,
                'phone' => $this->member?->phone,
            ],

            // 'package' => $this->member?->package
            //     ? new PackageResource($this->member->package)
            //     : null,

            'payments' => PaymentResource::collection(
                $this->whenLoaded('payments')
            ),

            'fines' => FineResource::collection(
                $this->whenLoaded('fines')
            ),
        ];
    }
}
