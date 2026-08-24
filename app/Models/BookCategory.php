<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Models\Book;

#[Fillable([
    'book_id',
    'category_id',
])]
class BookCategory extends Model
{
    public $timestamps = false;

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}