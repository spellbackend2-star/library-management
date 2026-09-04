<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Fine extends Model
{
    protected $fillable = [
        'borrow_id',
        'locker_assignment_id',
        'booking_seat_id',
        'member_id',
        'amount',
        'reason',
        'days_late',
        'issued_date',
        'paid_date',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'issued_date' => 'date',
        'paid_date' => 'date',
    ];

    public function borrow(): BelongsTo
    {
        return $this->belongsTo(Borrow::class);
    }

    public function bookingSeat(): BelongsTo
    {
        return $this->belongsTo(BookingSeat::class, 'booking_seat_id');
    }

    public function lockerAssignment(): BelongsTo
    {
        return $this->belongsTo(LockerAssignment::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}