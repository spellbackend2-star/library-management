<?php

namespace App\Http\Requests\Invoice;

use Illuminate\Foundation\Http\FormRequest;

class AddPaymentRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('payment_method'))) {
            $this->merge([
                'payment_method' => strtoupper(trim($this->input('payment_method'))),
            ]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => [
                'required',
                'numeric',
                'min:0.01',
            ],
            'currency' => [
                'nullable',
                'string',
                'max:3',
            ],
            'payment_method' => [
                'required',
                'string',
                'in:CARD,ESEWA,KHALTI,WALLET,CASH,LOYALTY_POINTS',
            ],
            'transaction_id' => [
                'nullable',
                'string',
                'max:255',
            ],
            'gateway_response' => [
                'nullable',
                'array',
            ],
            'paid_at' => [
                'nullable',
                'date',
            ],
            'extra_discount' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'return_url' => [
                'nullable',
                'required_if:payment_method,KHALTI,ESEWA',
                'url',
            ],
        ];
    }
}
