<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
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
            'booking_id' => [
                'required',
                'integer',
                'exists:bookings,id',
            ],
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
            'status' => [
                'nullable',
                'string',
                'in:PENDING,SUCCESS,FAILED,REFUNDED',
            ],
            'paid_at' => [
                'nullable',
                'date',
            ],
        ];
    }
}
