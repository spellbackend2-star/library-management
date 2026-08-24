<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use App\Models\Book;

#[Fillable([
    'name',
    'description',
])]
class Category extends Model
{
    public $timestamps = false;

    public function books(): BelongsToMany
    {
        return $this->belongsToMany(
            Book::class,
            'book_categories',
            'category_id',
            'book_id'
        );
    }
}