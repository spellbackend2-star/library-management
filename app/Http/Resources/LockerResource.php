<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LockerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'floor_id' => $this->floor_id,
            'floor_name' => $this->floor?->name,
            'locker_number' => $this->locker_number,
            'locker_type' => $this->locker_type,
            'location' => $this->location,
            'status' => $this->status,
            'member_id' => $this->when(
                $this->status === 'assigned',
                $this->activeAssignment?->member_id
            ),
            'member_name' => $this->when(
                $this->status === 'assigned',
                trim(($this->activeAssignment?->member?->first_name ?? '') . ' ' . ($this->activeAssignment?->member?->last_name ?? '')) ?: null
            ),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
