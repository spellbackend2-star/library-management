<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'room_type',
])]
class Room extends Model
{
    public $timestamps = false;

    public function seats(): HasMany
    {
        return $this->hasMany(Seat::class);
    }
}