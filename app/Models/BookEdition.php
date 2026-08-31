<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use App\Models\Book;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


#[Fillable([
    'book_id',
    'publisher_id',
    'isbn',
    'edition_number',
    'publication_year',
    'format',
])]
class BookEdition extends Model
{
    public $timestamps = false;

    protected $casts = [
        'publication_year' => 'integer',
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(Publisher::class);
    }

    public function copies(): HasMany
    {
        return $this->hasMany(Copy::class, 'edition_id');
    }
}