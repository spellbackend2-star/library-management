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
            'notes' => $this->notes,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];

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
