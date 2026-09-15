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
            'invoice_id' => $this->invoice_id,
            'borrow_id' => $this->borrow_id,
            'booking_seat_id' => $this->booking_seat_id,
            'locker_assignment_id' => $this->locker_assignment_id,
            'amount' => $this->amount,
            'reason' => $this->reason,
            'days_late' => $this->days_late,
            'issued_date' => $this->issued_date?->format('Y-m-d'),
            'paid_date' => $this->paid_date?->format('Y-m-d'),
            'status' => $this->status,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}