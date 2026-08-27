<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'room_id',
    'category_id',
    'seat_number',
    'has_power_outlet',
    'is_accessible',
    'status',
])]
class Seat extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'has_power_outlet' => 'boolean',
            'is_accessible' => 'boolean',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(SeatCategory::class, 'category_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(SeatBooking::class);
    }
}