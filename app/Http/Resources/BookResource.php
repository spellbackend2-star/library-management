<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $copyCounts = $this->copyCounts();

        return [
            'id' => $this->id,

            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'language' => $this->language,
            'description' => $this->description,
            'cover_image_url' => $this->cover_image_url,

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deleted_at,

            'total_copies' => $copyCounts['total'],
            'available_copies' => $copyCounts['available'],
            'unavailable_copies' => $copyCounts['total'] - $copyCounts['available'],

            'authors' => AuthorResource::collection($this->whenLoaded('authors')),
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'editions' => BookEditionResource::collection($this->whenLoaded('editions')),
        ];
    }

    private function copyCounts(): array
    {
        $book = $this->resource;
        $editions = $book->relationLoaded('editions')
            ? $book->getRelation('editions')
            : null;

        if ($editions !== null && $editions->every(
            fn ($edition) => $edition->relationLoaded('copies')
        )) {
            $total = $editions->sum(
                fn ($edition) => $edition->getRelation('copies')->count()
            );
            $available = $editions->sum(
                fn ($edition) => $edition->getRelation('copies')
                    ->where('status', 'available')
                    ->count()
            );
        } else {
            $counts = $book->editions()
                ->withCount([
                    'copies',
                    'copies as available_copies_count' => fn ($query) => $query
                        ->where('status', 'available'),
                ])
                ->get();

            $total = $counts->sum('copies_count');
            $available = $counts->sum('available_copies_count');
        }

        return [
            'total' => $total,
            'available' => $available,
        ];
    }
}
