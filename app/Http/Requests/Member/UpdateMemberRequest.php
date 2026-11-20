<?php

namespace App\Http\Requests\Member;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $memberId = $this->route('member');

        return [
            'package_id' => [
                'sometimes',
                'integer',
                'exists:packages,id',
            ],

            'first_name' => [
                'sometimes',
                'string',
                'max:100',
            ],

            'last_name' => [
                'sometimes',
                'string',
                'max:100',
            ],


            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('members', 'email')->ignore($memberId),
            ],
            'phone' => [
                'nullable',
                'string',
                'digits:10',
            ],

            'address' => [
                'nullable',
                'string',
                'max:255',
            ],

            'date_of_birth' => [
                'nullable',
                'date',
            ],

            'membership_start' => [
                'sometimes',
                'date',
            ],

            'membership_expiry' => [
                'nullable',
                'date',
                'after_or_equal:membership_start',
            ],

            'status' => [
                'sometimes',
                'in:active,inactive,suspended,expired,cancelled',
            ],

            'gender' => [
                'sometimes',
                'string',
                'max:20',
                'in:male,female,other',
            ],
        ];
    }

    public function messages(): array
    {
        return [];
    }
}
