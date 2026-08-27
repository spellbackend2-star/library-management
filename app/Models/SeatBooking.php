<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'description',
])]
class SeatCategory extends Model
{
    public $timestamps = false;

    public function seats(): HasMany
    {
        return $this->hasMany(Seat::class, 'category_id');
    }

    public function pricing(): HasMany
    {
        return $this->hasMany(SeatPricing::class, 'category_id');
    }
}