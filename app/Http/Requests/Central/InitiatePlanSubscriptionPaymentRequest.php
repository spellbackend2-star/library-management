<?php

namespace App\Http\Requests\Central;

use Illuminate\Foundation\Http\FormRequest;

class InitiatePlanSubscriptionPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('payment_method')) {
            $this->merge([
                'payment_method' => strtoupper(trim((string) $this->input('payment_method'))),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'subscription_plan_id' => [
                'required',
                'integer',
                'exists:subscription_plans,id',
            ],
            'payment_method' => [
                'required',
                'string',
                'in:CASH,KHALTI,ESEWA',
            ],
            'amount' => [
                'required',
                'numeric',
                'gt:0',
            ],
            'return_url' => [
                'nullable',
                'url',
            ],
            'coupon_id' => [
                'nullable',
                'integer',
                'exists:coupons,id',
            ],
        ];
    }
}
