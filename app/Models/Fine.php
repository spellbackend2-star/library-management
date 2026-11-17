<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fine extends Model
{
    protected $fillable = [
        'borrow_id',
        'locker_assignment_id',
        'booking_seat_id',
        'member_id',
        'created_by',
        'invoice_id',
        'amount',
        'reason',
        'source_type',
        'manual_reason',
        'notes',
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

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(FineAdjustment::class)->orderBy('created_at');
    }
}
