<?php

namespace App\Http\Requests\Member;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => is_string($this->email) ? strtolower(trim($this->email)) : $this->email,
            'phone' => is_string($this->phone) ? trim($this->phone) : $this->phone,
        ]);
    }

    public function rules(): array
    {
        return [
            'package_id' => [
                'required',
                'integer',
                'exists:packages,id',
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
                Rule::unique('members', 'email'),
            ],

            'phone' => [
                'required',
                'string',
                'digits:10',
                Rule::unique('members', 'phone'),
            ],

            'address' => [
                'required',
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
                'in:active,inactive,suspended,expired,cancelled',
            ],

            'gender' => [
                'required',
                'string',
                'max:20',
                'in:male,female,other',
            ],

            'with_invoice' => [
                'nullable',
                'boolean',
            ],

            'coupon_id' => [
                'nullable',
                'integer',
                'exists:coupons,id',
            ],
        ];

        return $rules;
    }

    public function messages(): array
    {
        return [
            'email.required' => 'The email field is required.',
            'email.email' => 'The email must be a valid email address.',
            'email.unique' => 'A member with this email address already exists.',
            'phone.digits' => 'The phone number must be exactly 10 digits.',
            'phone.unique' => 'A member with this phone number already exists.',
        ];
    }
}
