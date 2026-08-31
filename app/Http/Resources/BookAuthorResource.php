<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookAuthorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
           

            'book_id' => $this->book_id,
            'author_id' => $this->author_id,
            'author_role' => $this->author_role,

            'created_at' => $this->created_at,
        ];
    }
}
