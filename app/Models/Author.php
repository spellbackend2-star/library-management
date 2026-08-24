<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Book;

#[Fillable([
    'first_name',
    'last_name',
    'bio',
    'birth_date',
    'death_date',
])]
class Author extends Model
{
    public $timestamps = false;

    protected $casts = [
        'birth_date' => 'date',
        'death_date' => 'date',
    ];

    public function books(): BelongsToMany
    {
        return $this->belongsToMany(
            Book::class,
            'book_authors',
            'author_id',
            'book_id'
        )->withPivot('author_role');
    }
}