<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Book;

#[Fillable([
    'book_id',
    'barcode',
    'shelf_location',
    'condition',
    'status',
    'acquisition_date',
])]
class Copy extends Model
{
    protected $casts = [
        'acquisition_date' => 'date',
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}