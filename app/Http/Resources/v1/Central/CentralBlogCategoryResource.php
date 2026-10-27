<?php

namespace App\Http\Resources\v1\Central;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CentralBlogCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'blogs_count' => $this->whenCounted('blogs'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
