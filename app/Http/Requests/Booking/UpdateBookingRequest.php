<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'nullable',
                'in:PENDING,CONFIRMED,ACTIVE,COMPLETED,CANCELLED,EXPIRED',
            ],

            'amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'convenience_fee' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }
}
