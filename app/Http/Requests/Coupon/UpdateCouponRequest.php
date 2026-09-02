<?php

namespace App\Http\Requests\Coupon;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $couponId = $this->route('coupon');

        return [
            'code' => [
                'sometimes',
                'string',
                'max:30',
                Rule::unique('coupons', 'code')->ignore($couponId),
            ],

            'discount_type' => [
                'sometimes',
                'in:FLAT,PERCENT',
            ],

            'discount_value' => [
                'sometimes',
                'numeric',
                'min:0',
            ],

            'max_discount' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'min_order_value' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'valid_from' => [
                'nullable',
                'date',
            ],

            'valid_until' => [
                'nullable',
                'date',
                'after_or_equal:valid_from',
            ],

            'max_uses' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'max_uses_per_user' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],
        ];
    }
}
