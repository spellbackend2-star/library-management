<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'booking_id' => [
                'sometimes',
                'integer',
                'exists:bookings,id',
            ],
            'amount' => [
                'sometimes',
                'numeric',
                'min:0',
            ],
            'currency' => [
                'nullable',
                'string',
                'max:3',
            ],
            'payment_method' => [
                'sometimes',
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
                'sometimes',
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
