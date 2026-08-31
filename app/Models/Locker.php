<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'floor_id',
    'locker_number',
    'locker_type',
    'location',
    'status',
])]
class Locker extends Model
{
    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(LockerAssignment::class);
    }
}