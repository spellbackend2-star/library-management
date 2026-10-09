<?php

namespace App\Http\Requests\Central;

use Illuminate\Foundation\Http\FormRequest;

class IndexCentralSubscriptionPaymentRequest extends FormRequest
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
            'tenant_id' => ['nullable', 'string'],
            'subscription_id' => ['nullable', 'integer'],
            'status' => [
                'nullable',
                'string',
                'in:PENDING,SUCCESS,FAILED',
            ],
            'payment_method' => [
                'nullable',
                'string',
                'in:CASH,KHALTI,ESEWA',
            ],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
