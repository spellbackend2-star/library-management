<?php

namespace App\Services\Central;

use App\Models\Coupon;
use Illuminate\Validation\ValidationException;

class CentralCouponService
{
    public function calculateRegistrationCoupon(?int $couponId, float $amount): array
    {
        if (! $couponId) {
            return [
                'coupon_id' => null,
                'coupon_discount' => 0.0,
            ];
        }

        $coupon = Coupon::query()
            ->lockForUpdate()
            ->find($couponId);

        if (! $coupon) {
            throw ValidationException::withMessages([
                'coupon_id' => ['The selected coupon is invalid.'],
            ]);
        }

        if (! $coupon->is_active) {
            throw ValidationException::withMessages([
                'coupon_id' => ['This coupon is inactive.'],
            ]);
        }

        if ($coupon->valid_from && now()->lt($coupon->valid_from)) {
            throw ValidationException::withMessages([
                'coupon_id' => ['This coupon is not yet valid.'],
            ]);
        }

        if ($coupon->valid_until && now()->gt($coupon->valid_until)) {
            throw ValidationException::withMessages([
                'coupon_id' => ['This coupon has expired.'],
            ]);
        }

        if ($coupon->max_uses !== null && $coupon->used_count >= $coupon->max_uses) {
            throw ValidationException::withMessages([
                'coupon_id' => ['Coupon usage limit reached.'],
            ]);
        }

        if ($amount < (float) $coupon->min_order_value) {
            throw ValidationException::withMessages([
                'coupon_id' => ['Minimum order value not met for this coupon.'],
            ]);
        }

        $discount = match (strtoupper((string) $coupon->discount_type)) {
            'PERCENT' => round($amount * ((float) $coupon->discount_value / 100), 2),
            'FLAT' => round((float) $coupon->discount_value, 2),
            default => 0.0,
        };

        if ($coupon->max_discount !== null) {
            $discount = min($discount, (float) $coupon->max_discount);
        }

        return [
            'coupon_id' => $coupon->id,
            'coupon_discount' => min($discount, $amount),
        ];
    }

    public function calculatePaymentCoupon(?int $couponId, float $amount): array
    {
        if (! $couponId) {
            return ['coupon_id' => null, 'coupon_discount' => 0.0, 'error' => null];
        }

        $coupon = Coupon::lockForUpdate()->find($couponId);

        if (! $coupon) {
            return ['coupon_id' => null, 'coupon_discount' => 0.0, 'error' => 'The selected coupon is invalid.'];
        }

        if (! $coupon->is_active) {
            return ['coupon_id' => null, 'coupon_discount' => 0.0, 'error' => 'This coupon is inactive.'];
        }

        if ($coupon->valid_from && now() < $coupon->valid_from) {
            return ['coupon_id' => null, 'coupon_discount' => 0.0, 'error' => 'This coupon is not yet valid.'];
        }

        if ($coupon->valid_until && now() > $coupon->valid_until) {
            return ['coupon_id' => null, 'coupon_discount' => 0.0, 'error' => 'This coupon has expired.'];
        }

        if ($coupon->max_uses !== null && (int) $coupon->used_count >= (int) $coupon->max_uses) {
            return ['coupon_id' => null, 'coupon_discount' => 0.0, 'error' => 'Coupon usage limit reached.'];
        }

        if ($amount < (float) $coupon->min_order_value) {
            return ['coupon_id' => null, 'coupon_discount' => 0.0, 'error' => 'Minimum order value not met for this coupon.'];
        }

        $discount = match (strtoupper((string) $coupon->discount_type)) {
            'PERCENT' => round($amount * ((float) $coupon->discount_value / 100), 2),
            'FLAT' => round((float) $coupon->discount_value, 2),
            default => 0.0,
        };

        if ($coupon->max_discount !== null) {
            $discount = min($discount, (float) $coupon->max_discount);
        }

        return [
            'coupon_id' => $coupon->id,
            'coupon_discount' => min($discount, $amount),
            'error' => null,
        ];
    }

    public function recordUsage(?int $couponId): void
    {
        if ($couponId) {
            Coupon::whereKey($couponId)->increment('used_count');
        }
    }
}
