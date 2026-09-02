<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'member_id' => $this->member_id,
            'package_id' => $this->package_id,
            'booking_type' => $this->booking_type,
            'status' => $this->status,
            'amount' => $this->amount,
            'subtotal' => $this->subtotal,
            'tax_amount' => $this->tax_amount,
            'discount_amount' => $this->discount_amount,
            'convenience_fee' => $this->convenience_fee,
            'total_amount' => $this->total_amount,
            'coupon_id' => $this->coupon_id,
            'payment_status' => $this->payment_status,
            'booking_source' => $this->booking_source,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'package' => [
                'id' => $this->package?->id,
                'name' => $this->package?->name,
                'price' => $this->package?->price,
                'duration' => $this->package?->duration,
                'duration_unit' => $this->package?->duration_unit,
            ],
        ];

        if ($this->whenLoaded('coupon')) {
            $data['coupon'] = [
                'id' => $this->coupon?->id,
                'code' => $this->coupon?->code,
                'discount_type' => $this->coupon?->discount_type,
                'discount_value' => $this->coupon?->discount_value,
                'max_discount' => $this->coupon?->max_discount,
                'min_order_value' => $this->coupon?->min_order_value,
                'valid_from' => $this->coupon?->valid_from,
                'valid_until' => $this->coupon?->valid_until,
                'used_count' => $this->coupon?->used_count,
                'is_active' => $this->coupon?->is_active,
            ];
        }

        if ($this->whenLoaded('bookingSeats')) {
            $data['seats'] = BookingSeatResource::collection($this->bookingSeats);
        }

        if ($this->whenLoaded('borrows')) {
            $data['books'] = BorrowResource::collection($this->borrows);
        }

        if ($this->whenLoaded('lockerAssignments')) {
            $data['lockers'] = LockerAssigmentsResource::collection($this->lockerAssignments);
        }

        return $data;
    }
}
