<?php

namespace App\Http\Requests\Central;

use Illuminate\Foundation\Http\FormRequest;

class PaySubscriptionPaymentRequest extends FormRequest
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
        ];
    }
}
