<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Book;
use App\Models\Author;

#[Fillable([
    'book_id',
    'author_id',
    'author_role',
])]
class BookAuthor extends Model
{
    public $timestamps = false;

    protected $table = 'book_authors';

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }
}