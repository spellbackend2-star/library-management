<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SeatResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'room_id' => $this->room_id,
            'category_id' => $this->category_id,
            'seat_number' => $this->seat_number,
            'has_power_outlet' => $this->has_power_outlet,
            'is_accessible' => $this->is_accessible,
            'status' => $this->status,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
