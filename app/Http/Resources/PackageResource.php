<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PackageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'duration' => $this->duration,
            'duration_unit' => $this->duration_unit,
            'max_book_loans' => $this->max_book_loans,
            'max_borrow_days' => $this->max_borrow_days,
            'seat_access_allowed' => $this->seat_access_allowed,
            'max_seat_hours_per_day' => $this->max_seat_hours_per_day,
            'locker_allowed' => $this->locker_allowed,
            'locker_type' => $this->locker_type,
            'max_locker_hours_per_day' => $this->max_locker_hours_per_day,
            'is_active' => $this->is_active,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
