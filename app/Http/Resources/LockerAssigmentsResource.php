<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LockerAssigmentsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'locker_id' => $this->locker_id,
            'locker_number' => $this->locker?->locker_number,
            'locker_type' => $this->locker?->locker_type,
            'locker_name' => $this->locker?->locker_number . ' - ' . $this->locker?->locker_type,
            'member_id' => $this->member_id,
            'member_name' => trim(($this->member?->first_name ?? '') . ' ' . ($this->member?->last_name ?? '')),
            'assigned_date' => $this->assigned_date?->format('Y-m-d H:i:s'),
            'expiry_date' => $this->expiry_date?->format('Y-m-d H:i:s'),
            'returned_date' => $this->returned_date?->format('Y-m-d'),
            'status' => $this->status,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
