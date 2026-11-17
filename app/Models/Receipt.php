<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'payment_id',
    'receipt_number',
    'amount',
    'receipt_date',
    'member_id',
])]
class Receipt extends Model
{
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'receipt_date' => 'datetime',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
