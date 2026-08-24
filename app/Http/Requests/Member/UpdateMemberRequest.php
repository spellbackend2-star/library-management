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
            'membership_type_id' => [
                'sometimes',
                'integer',
                'exists:membership_types,id',
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
                'max:30',
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
                'in:active,suspended,expired,cancelled',
            ],
        ];
    }
}