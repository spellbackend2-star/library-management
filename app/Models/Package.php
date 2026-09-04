<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'duration',
        'duration_unit',
        'max_book_loans',
        'max_borrow_days',
        'seat_access_allowed',
        'max_seat_hours_per_day',
        'locker_allowed',
        'locker_type',
        'max_locker_hours_per_day',
        'overdue_fine_per_day',
        'seat_overdue_fine_per_hour',
        'locker_overdue_fine_per_day',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'duration' => 'integer',
        'max_book_loans' => 'integer',
        'max_borrow_days' => 'integer',
        'seat_access_allowed' => 'boolean',
        'max_seat_hours_per_day' => 'decimal:2',
        'locker_allowed' => 'boolean',
        'max_locker_hours_per_day' => 'decimal:2',
        'overdue_fine_per_day' => 'decimal:2',
        'seat_overdue_fine_per_hour' => 'decimal:2',
        'locker_overdue_fine_per_day' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Package has many members.
     */
    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }
}