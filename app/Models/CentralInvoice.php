<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'tenant_id',
    'subscription_id',
    'subscription_payment_id',
    'invoice_number',
    'invoice_type',
    'subtotal',
    'tax',
    'discount',
    'coupon_id',
    'coupon_discount',
    'total_amount',
    'paid_amount',
    'remaining_amount',
    'currency',
    'currency_symbol',
    'status',
    'due_date',
    'notes',
])]
class CentralInvoice extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * Central invoices live in the "invoices" table of the central
     * database. This is a different physical table from the tenant
     * "invoices" table even though both share the same name.
     */
    protected $table = 'invoices';

    /**
     * Invoice number prefix for central invoices. Kept separate from
     * the tenant numbering so the two can never collide.
     */
    public const INVOICE_PREFIX = 'INV';

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'tax' => 'decimal:2',
            'discount' => 'decimal:2',
            'coupon_id' => 'integer',
            'coupon_discount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'remaining_amount' => 'decimal:2',
            'due_date' => 'date',
        ];
    }

    /**
     * Always read and write through the central connection, even when a
     * tenant has been initialized and the default connection is switched.
     */
    public function getConnectionName()
    {
        return config('tenancy.database.central_connection')
            ?? config('database.default');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function subscriptionPayment(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPayment::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    /**
     * Generate the next central invoice number, e.g. "INV-2026-1001".
     */
    public static function generateInvoiceNumber(): string
    {
        $year = now()->format('Y');

        $last = static::withTrashed()
            ->whereYear('created_at', $year)
            ->orderByDesc('id')
            ->first();

        $next = $last ? ((int) $last->id + 1) : 1000;

        return self::INVOICE_PREFIX.'-'.$year.'-'.$next;
    }

    /**
     * Recalculate the derived money fields and status.
     */
    public function recalculate(): self
    {
        $subtotal = (float) $this->subtotal;
        $tax = (float) $this->tax;
        $discount = (float) $this->discount;
        $couponDiscount = (float) $this->coupon_discount;
        $total = round($subtotal + $tax - $discount - $couponDiscount, 2);

        $paid = (float) $this->paid_amount;
        $remaining = max(0, round($total - $paid, 2));

        $status = $this->status;

        if (! in_array($status, ['cancelled', 'refunded'], true)) {
            $status = match (true) {
                $remaining <= 0 => 'paid',
                $paid > 0 => 'partially_paid',
                $this->due_date && $this->due_date->isPast() => 'overdue',
                default => 'unpaid',
            };
        }

        $this->update([
            'total_amount' => $total,
            'remaining_amount' => $remaining,
            'status' => $status,
        ]);

        return $this;
    }

    public function markAsPaid(?float $amount = null): void
    {
        if ($amount !== null) {
            $this->paid_amount = round((float) $this->paid_amount + $amount, 2);
        } else {
            $this->paid_amount = (float) $this->total_amount;
        }

        $this->recalculate();
    }
}
