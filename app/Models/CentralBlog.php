<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CentralBlog extends Model
{
    protected $fillable = [
        'category_id', 'title', 'slug', 'excerpt', 'content', 'featured_image',
        'meta_title', 'meta_description', 'is_published', 'published_at',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CentralBlogCategory::class, 'category_id');
    }
}
