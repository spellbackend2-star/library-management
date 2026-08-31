<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'member_id',
    'package_id',
    'booking_type',
    'status',
    'notes',
])]
class Booking extends Model
{
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function bookingSeats(): HasMany
    {
        return $this->hasMany(BookingSeat::class);
    }

    public function borrows(): HasMany
    {
        return $this->hasMany(Borrow::class);
    }

    public function lockerAssignments(): HasMany
    {
        return $this->hasMany(LockerAssignment::class);
    }
}
