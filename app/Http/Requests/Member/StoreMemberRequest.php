<?php

namespace App\Http\Requests\Member;

use Illuminate\Foundation\Http\FormRequest;

class StoreMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'membership_type_id' => [
                'required',
                'integer',
                'exists:membership_types,id',
            ],

            'first_name' => [
                'required',
                'string',
                'max:100',
            ],

            'last_name' => [
                'required',
                'string',
                'max:100',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                'unique:members,email',
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
                'nullable',
                'date',
            ],

            'membership_expiry' => [
                'nullable',
                'date',
                'after_or_equal:membership_start',
            ],

            'status' => [
                'nullable',
                'in:active,suspended,expired,cancelled',
            ],
        ];
    }
}