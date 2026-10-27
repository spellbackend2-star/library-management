<?php

namespace App\Http\Resources\v1\Central;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CentralPageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'title' => $this->title,
            'slug' => $this->slug,
            'content' => $this->content,

            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,

            'is_published' => $this->is_published,
            'published_at' => $this->published_at,

            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,

            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'updater' => $this->whenLoaded('updater', fn () => [
                'id' => $this->updater->id,
                'name' => $this->updater->name,
            ]),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
