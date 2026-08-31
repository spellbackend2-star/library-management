<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingSeatResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'booking_id' => $this->booking_id,
            'seat_id' => $this->seat_id,
            'start_at' => $this->start_at?->format('Y-m-d H:i:s'),
            'end_at' => $this->end_at?->format('Y-m-d H:i:s'),
            'status' => $this->status,

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
