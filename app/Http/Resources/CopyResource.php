<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CopyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'book_id' => $this->book_id,
            'barcode' => $this->barcode,
            'shelf_location' => $this->shelf_location,
            'condition' => $this->condition,
            'status' => $this->status,
            'acquisition_date' => $this->acquisition_date?->format('Y-m-d'),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
