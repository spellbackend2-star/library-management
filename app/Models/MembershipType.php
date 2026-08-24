<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'description',
    'max_book_loans',
    'max_seat_hours_per_day',
    'annual_fee',
])]
class MembershipType extends Model
{
    public $timestamps = false;

    protected $casts = [
        'annual_fee' => 'decimal:2',
    ];

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }
}