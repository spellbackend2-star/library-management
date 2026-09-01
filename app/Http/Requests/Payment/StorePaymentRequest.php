<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
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
                'min:0',
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
