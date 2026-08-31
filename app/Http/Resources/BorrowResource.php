<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BorrowResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'copy_id' => $this->copy_id,
            'member_id' => $this->member_id,
            'staff_id' => $this->staff_id,
            'checkout_date' => $this->checkout_date?->format('Y-m-d H:i:s'),
            'due_date' => $this->due_date?->format('Y-m-d'),
            'return_date' => $this->return_date?->format('Y-m-d H:i:s'),
            'renewal_count' => $this->renewal_count,
            'status' => $this->status,

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
