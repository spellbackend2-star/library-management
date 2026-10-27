<?php

namespace App\Http\Resources\v1\Central;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CentralBlogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category?->id,
                'name' => $this->category?->name,
                'slug' => $this->category?->slug,
            ]),
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'content' => $this->content,
            'featured_image' => $this->featured_image,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'is_published' => $this->is_published,
            'published_at' => $this->published_at,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
