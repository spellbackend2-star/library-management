<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    public function activeAssignment(): HasOne
    {
        return $this->hasOne(LockerAssignment::class)
            ->where('status', 'active')
            ->where('assigned_date', '<=', now())
            ->where('expiry_date', '>', now())
            ->latestOfMany();
    }
}
