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
    'amount',
    'subtotal',
    'tax_amount',
    'discount_amount',
    'convenience_fee',
    'total_amount',
    'coupon_id',
    'payment_status',
    'booking_source',
    'notes',
    'booked_by_user_id',
    'expires_at',
    'confirmed_at',
    'cancelled_at',
])]
class Booking extends Model
{
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'convenience_fee' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'is_active' => 'boolean',
            'expires_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
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

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
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
