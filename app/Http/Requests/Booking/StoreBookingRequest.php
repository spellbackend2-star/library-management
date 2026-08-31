<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'member_id' => [
                'required',
                'integer',
                'exists:members,id',
            ],

            'bookings' => [
                'required',
                'array',
                'min:1',
            ],

            'bookings.*.type' => [
                'required',
                'string',
                'in:seat,book,locker',
            ],

            'bookings.*.seat_id' => [
                'required_if:bookings.*.type,seat',
                'integer',
                'exists:seats,id',
            ],

            'bookings.*.copy_id' => [
                'required_if:bookings.*.type,book',
                'integer',
                'exists:copies,id',
            ],

            'bookings.*.locker_id' => [
                'required_if:bookings.*.type,locker',
                'integer',
                'exists:lockers,id',
            ],

            'bookings.*.start_at' => [
                'required_if:bookings.*.type,seat,locker',
                'date',
            ],

            'bookings.*.end_at' => [
                'required_if:bookings.*.type,seat,locker',
                'date',
                'after:bookings.*.start_at',
            ],

            'bookings.*.amount' => [
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

        return $rules;
    }
}
