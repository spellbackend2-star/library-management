<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'language' => $this->language,
            'description' => $this->description,
            'cover_image_url' => $this->cover_image_url,

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deleted_at,

            'authors' => AuthorResource::collection($this->whenLoaded('authors')),
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'editions' => BookEditionResource::collection($this->whenLoaded('editions')),
        ];
    }
}
