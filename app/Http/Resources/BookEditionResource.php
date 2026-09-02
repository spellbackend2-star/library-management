<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookEditionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'book_id' => $this->book_id,
            'book_name' => $this->book?->title,
            'publisher_id' => $this->publisher_id,
            'isbn' => $this->isbn,
            'edition_number' => $this->edition_number,
            'publication_year' => $this->publication_year,
            'format' => $this->format,
            'created_at' => $this->created_at,

            'publisher' => new PublisherResource($this->whenLoaded('publisher')),
            'copies' => CopyResource::collection($this->whenLoaded('copies')),
        ];
    }
}
