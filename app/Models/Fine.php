<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'borrow_id',
    'seat_booking_id',
    'member_id',
    'amount',
    'reason',
    'issued_date',
    'paid_date',
    'status',
])]
class Fine extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'issued_date' => 'date',
            'paid_date' => 'date',
        ];
    }

    public function borrow(): BelongsTo
    {
        return $this->belongsTo(Borrow::class);
    }

    public function seatBooking(): BelongsTo
    {
        return $this->belongsTo(BookingSeat::class, 'seat_booking_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}