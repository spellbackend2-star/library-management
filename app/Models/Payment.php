<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

#[Fillable([
    'booking_id',
    'invoice_id',
    'member_id',
    'amount',
    'extra_discount',
    'currency',
    'payment_method',
    'transaction_id',
    'gateway_reference',
    'payment_url',
    'gateway_response',
    'status',
    'payment_date',
    'paid_at',
])]
class Payment extends Model
{
    public $timestamps = true;

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'extra_discount' => 'decimal:2',
            'gateway_response' => 'array',
            'payment_date' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::created(fn (Payment $payment) => $payment->createReceiptIfSuccessful());
        static::updated(fn (Payment $payment) => $payment->createReceiptIfSuccessful());
    }

    public function ensureReceipt(): ?Receipt
    {
        if ($this->status !== 'SUCCESS') {
            return null;
        }

        $this->loadMissing('receipt');

        if ($this->receipt) {
            return $this->receipt;
        }

        $receipt = Receipt::firstOrCreate(
            ['payment_id' => $this->id],
            [
                'receipt_number' => 'TMP-'.Str::upper((string) Str::uuid()),
                'amount' => $this->amount,
                'receipt_date' => $this->paid_at ?? $this->payment_date ?? now(),
                'member_id' => $this->member_id,
            ],
        );

        $year = $receipt->receipt_date?->format('Y') ?? now()->format('Y');
        $receiptNumber = 'RCP-'.$year.'-'.str_pad((string) $receipt->id, 6, '0', STR_PAD_LEFT);

        if ($receipt->receipt_number !== $receiptNumber) {
            $receipt->forceFill(['receipt_number' => $receiptNumber])->save();
        }

        $this->setRelation('receipt', $receipt);

        return $receipt;
    }

    protected function createReceiptIfSuccessful(): void
    {
        $this->ensureReceipt();
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function receipt(): HasOne
    {
        return $this->hasOne(Receipt::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
