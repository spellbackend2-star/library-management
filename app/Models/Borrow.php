<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'copy_id',
    'member_id',
    'staff_id',
    'checkout_date',
    'due_date',
    'return_date',
    'renewal_count',
    'status',
])]
class Borrow extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'checkout_date' => 'datetime',
            'due_date' => 'date',
            'return_date' => 'datetime',
            'renewal_count' => 'integer',
        ];
    }

    public function copy(): BelongsTo
    {
        return $this->belongsTo(Copy::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}