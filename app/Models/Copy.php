<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'edition_id',
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

    public function edition(): BelongsTo
    {
        return $this->belongsTo(BookEdition::class, 'edition_id');
    }

    public function borrows(): HasMany
    {
        return $this->hasMany(Borrow::class);
    }

    public function currentBorrows(): HasMany
    {
        return $this->borrows()->whereIn('status', ['active', 'overdue']);
    }
}
