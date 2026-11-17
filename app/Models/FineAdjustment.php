<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FineAdjustment extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'fine_id',
        'actor_user_id',
        'action',
        'old_amount',
        'new_amount',
        'note',
    ];

    protected $casts = [
        'old_amount' => 'decimal:2',
        'new_amount' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function fine(): BelongsTo
    {
        return $this->belongsTo(Fine::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
