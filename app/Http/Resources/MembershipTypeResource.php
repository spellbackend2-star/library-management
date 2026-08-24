<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MembershipTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'max_book_loans' => $this->max_book_loans,
            'max_seat_hours_per_day' => $this->max_seat_hours_per_day,
            'annual_fee' => $this->annual_fee,
            'created_at' => $this->created_at,
        ];
    }
}