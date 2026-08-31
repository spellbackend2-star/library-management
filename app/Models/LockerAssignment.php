<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'booking_id',
    'locker_id',
    'member_id',
    'assigned_date',
    'expiry_date',
    'returned_date',
    'status',
])]
class LockerAssignment extends Model
{
    protected function casts(): array
    {
        return [
            'assigned_date' => 'date',
            'expiry_date' => 'date',
            'returned_date' => 'date',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function locker(): BelongsTo
    {
        return $this->belongsTo(Locker::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}