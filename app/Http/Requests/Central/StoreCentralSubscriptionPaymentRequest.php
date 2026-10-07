<?php

namespace App\Http\Requests\Central;

use Illuminate\Foundation\Http\FormRequest;

class StoreCentralSubscriptionPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('payment_method')) {
            $this->merge([
                'payment_method' => strtoupper((string) $this->input('payment_method')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'subscription_id' => [
                'required',
                'integer',
                'exists:subscriptions,id',
            ],
            'payment_method' => [
                'required',
                'string',
                'regex:/^(CASH|KHALTI|ESEWA)$/i',
            ],
            'amount' => [
                'nullable',
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
