<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'member_id' => $this->member_id,
            'member_name' => trim(($this->member?->first_name ?? '') . ' ' . ($this->member?->last_name ?? '')),
            'created_by' => $this->created_by,
            'created_by_name' => $this->creator?->name,
            'invoice_id' => $this->invoice_id,
            'invoice_number' => $this->invoice?->invoice_number,
            'borrow_id' => $this->borrow_id,
            'book_name' => $this->borrow?->copy?->edition?->book?->title,
            'copy_name' => $this->borrow?->copy?->barcode,
            'booking_seat_id' => $this->booking_seat_id,
            'seat_name' => $this->bookingSeat?->seat?->seat_number,
            'locker_assignment_id' => $this->locker_assignment_id,
            'locker_name' => $this->lockerAssignment?->locker?->locker_number,
            'amount' => $this->amount,
            'reason' => $this->manual_reason ?? $this->reason,
            'reason_code' => $this->reason,
            'source_type' => $this->source_type ?? 'automatic',
            'notes' => $this->notes,
            'days_late' => $this->days_late,
            'issued_date' => $this->issued_date?->format('Y-m-d'),
            'paid_date' => $this->paid_date?->format('Y-m-d'),
            'status' => $this->status,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'adjustments' => FineAdjustmentResource::collection($this->whenLoaded('adjustments')),
        ];
    }
}
