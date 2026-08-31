<?php

namespace App\Http\Requests\SeatBooking;

use Illuminate\Foundation\Http\FormRequest;

class StoreSeatBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'seat_id' => [
                'required',
                'integer',
                'exists:seats,id',
            ],

            'member_id' => [
                'required',
                'integer',
                'exists:members,id',
            ],

            'start_time' => [
                'required',
                'date',
            ],

            'end_time' => [
                'required',
                'date',
                'after:start_time',
            ],

            'status' => [
                'nullable',
                'in:booked,active,completed,cancelled,no_show',
            ],
        ];
    }
}
