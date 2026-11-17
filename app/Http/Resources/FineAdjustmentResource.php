<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FineAdjustmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'old_amount' => $this->old_amount,
            'new_amount' => $this->new_amount,
            'note' => $this->note,
            'actor_user_id' => $this->actor_user_id,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
