<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
                'nullable',
                Rule::requiredIf(fn () => ! $this->filled('staff_id')),
                'integer',
                'exists:members,id',
            ],

            'staff_id' => [
                'nullable',
                Rule::requiredIf(fn () => ! $this->filled('member_id')),
                'integer',
                'exists:staff,id',
            ],

            'due_date' => [
                'nullable',
                Rule::requiredIf(fn () => ! $this->filled('member_id')),
                'date',
                'after_or_equal:today',
            ],

            'amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'bookings' => [
                'required',
                'array',
                'min:1',
            ],

            'bookings.*.type' => [
                'required',
                'string',
                'in:seat,book,locker,package',
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

            'coupon_id' => [
                'nullable',
                'integer',
                'exists:coupons,id',
            ],

            'convenience_fee' => [
                'nullable',
                'numeric',
                'min:0',
            ],
        ];

        return $rules;
    }
}
