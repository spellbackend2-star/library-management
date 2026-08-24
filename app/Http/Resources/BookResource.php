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

            'created_at' => $this->created_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
