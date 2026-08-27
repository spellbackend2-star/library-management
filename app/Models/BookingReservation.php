<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'book_id',
    'member_id',
    'reservation_date',
    'expiry_date',
    'status',
])]
class BookReservation extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'reservation_date' => 'datetime',
            'expiry_date' => 'date',
        ];
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}