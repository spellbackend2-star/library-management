<?php

namespace App\Http\Requests\SeatBooking;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSeatBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $bookingId = $this->route('seat_booking');

        return [
            'seat_id' => [
                'sometimes',
                'integer',
                'exists:seats,id',
            ],

            'member_id' => [
                'sometimes',
                'integer',
                'exists:members,id',
            ],

            'start_time' => [
                'sometimes',
                'date',
            ],

            'end_time' => [
                'sometimes',
                'date',
                'after:start_time',
            ],

            'status' => [
                'sometimes',
                'in:booked,active,completed,cancelled,no_show',
            ],
        ];
    }
}
