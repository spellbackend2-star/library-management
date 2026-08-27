<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'seat_id',
    'member_id',
    'pricing_id',
    'start_time',
    'end_time',
    'total_amount',
    'status',
])]
class SeatBooking extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'total_amount' => 'decimal:2',
        ];
    }

    public function seat(): BelongsTo
    {
        return $this->belongsTo(Seat::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function pricing(): BelongsTo
    {
        return $this->belongsTo(SeatPricing::class, 'pricing_id');
    }
}