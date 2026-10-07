<?php

namespace App\Http\Requests\Central;

use Illuminate\Foundation\Http\FormRequest;

class CentralRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'owner' => ['required', 'string', 'max:255'],

            'company_name' => ['required', 'string', 'max:255'],

            'phone' => ['required', 'string', 'max:50', 'unique:tenants,phone'],

            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:tenants,owner_email',
            ],

            'password' => ['required', 'string', 'min:8'],

            'subdomain' => [
                'required',
                'string',
                'max:255',
                'unique:tenants,tenant_code',
            ],

            'subscription_plan_id' => [
                'required',
                'integer',
                'exists:subscription_plans,id',
            ],

            'coupon_id' => [
                'nullable',
                'integer',
                'exists:coupons,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'This email is already registered. Please use another email.',

            'phone.unique' => 'This phone number is already registered. Please use another phone number.',

            'subdomain.unique' => 'This subdomain is already taken. Please choose another one.',
        ];
    }
}