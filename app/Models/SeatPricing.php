<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'category_id',
    'billing_period',
    'price',
    'effective_from',
    'effective_to',
    'is_active',
])]
class SeatPricing extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(SeatCategory::class, 'category_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(SeatBooking::class, 'pricing_id');
    }
}