<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'floor_number',
    'description',
])]
class Floor extends Model
{
    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function lockers(): HasMany
    {
        return $this->hasMany(Locker::class);
    }
}