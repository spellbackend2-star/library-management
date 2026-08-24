<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'book_id' => $this->book_id,
            'category_id' => $this->category_id,

            'created_at' => $this->created_at,
        ];
    }
}
