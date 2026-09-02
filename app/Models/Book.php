<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'title',
    'subtitle',
    'language',
    'description',
    'cover_image_url',
])]
class Book extends Model
{
    use SoftDeletes;

    const DELETED_AT = 'deleted_at';

    const UPDATED_AT = null;

    public $timestamps = true;

    public function editions(): HasMany
    {
        return $this->hasMany(BookEdition::class);
    }

    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(
            Author::class,
            'book_authors',
            'book_id',
            'author_id'
        )->withPivot('author_role');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            Category::class,
            'book_categories',
            'book_id',
            'category_id'
        );
    }

    // public function reservations(): HasMany
    // {
    //     return $this->hasMany(BookReservation::class);
    // }
}