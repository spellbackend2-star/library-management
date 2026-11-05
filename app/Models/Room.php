<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'floor_id',
    'name',
    'room_type',
    'status',
])]
class Room extends Model
{
    public $timestamps = false;

    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    public function seats(): HasMany
    {
        return $this->hasMany(Seat::class);
    }
}
