<?php

namespace App\Http\Requests\MembershipType;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMembershipTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $membershipTypeId = $this->route('membership_type');

        return [
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('membership_types', 'name')
                    ->ignore($membershipTypeId),
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