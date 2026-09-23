<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'tenant_id',
    'subscription_id',
    'invoice_number',
    'invoice_type',
    'subtotal',
    'tax',
    'discount',
    'total_amount',
    'paid_amount',
    'remaining_amount',
    'status',
    'due_date',
    'notes',
])]
class SubscriptionInvoice extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'tax' => 'decimal:2',
            'discount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'remaining_amount' => 'decimal:2',
            'due_date' => 'date',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public static function generateInvoiceNumber(): string
    {
        $last = self::orderByDesc('id')->first();
        $next = $last ? ((int) $last->id + 1) : 1000;

        $year = now()->format('Y');

        return 'SUB-INV-'.$year.'-'.$next;
    }

    public function markAsPaid(float $amount = null): void
    {
        if ($amount !== null) {
            $newPaid = round((float) $this->paid_amount + $amount, 2);
        } else {
            $newPaid = (float) $this->total_amount;
        }

        $remaining = max(0, round((float) $this->total_amount - $newPaid, 2));

        $status = $remaining <= 0 ? 'paid' : 'partially_paid';

        $this->update([
            'paid_amount' => $newPaid,
            'remaining_amount' => $remaining,
            'status' => $status,
        ]);
    }
}
