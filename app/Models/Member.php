<?php

namespace App\Models;

use App\Models\Package;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'package_id',
    'first_name',
    'last_name',
    'email',
    'phone',
    'address',
    'date_of_birth',
    'membership_start',
    'membership_expiry',
    'status',
])]
class Member extends Model
{
    protected $casts = [
        'date_of_birth' => 'date',
        'membership_start' => 'date',
        'membership_expiry' => 'date',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    // public function borrows(): HasMany
    // {
    //     return $this->hasMany(Borrow::class);
    // }

    // public function bookReservations(): HasMany
    // {
    //     return $this->hasMany(BookReservation::class);
    // }

    // public function seatBookings(): HasMany
    // {
    //     return $this->hasMany(SeatBooking::class);
    // }

    // public function fines(): HasMany
    // {
    //     return $this->hasMany(Fine::class);
    // }

    // public function invoices(): HasMany
    // {
    //     return $this->hasMany(Invoice::class);
    // }

    // public function payments(): HasMany
    // {
    //     return $this->hasMany(Payment::class);
    // }
}