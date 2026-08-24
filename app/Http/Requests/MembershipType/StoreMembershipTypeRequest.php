<?php

namespace App\Http\Requests\MembershipType;

use Illuminate\Foundation\Http\FormRequest;

class StoreMembershipTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:50',
                'unique:membership_types,name',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'max_book_loans' => [
                'required',
                'integer',
                'min:0',
            ],

            'max_seat_hours_per_day' => [
                'required',
                'integer',
                'min:0',
            ],

            'annual_fee' => [
                'required',
                'numeric',
                'min:0',
            ],
        ];
    }
}