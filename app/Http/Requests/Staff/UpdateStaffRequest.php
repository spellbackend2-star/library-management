<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $staffId = $this->route('staff');

        $staff = \App\Models\Staff::findOrFail($staffId);

        return [
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
                Rule::unique('users', 'email')->ignore($staff->user_id),
                Rule::unique('staff', 'email')->ignore($staffId),
            ],

            'phone' => [
                'nullable',
                'string',
                'max:30',
            ],

            'role' => [
                'sometimes',
                'string',
                'exists:roles,name',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === 'admin' && ! $this->user()?->hasRole('admin', 'api')) {
                        $fail('Only a tenant admin can assign the admin role.');
                    }
                },
            ],

            'hire_date' => [
                'sometimes',
                'date',
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }
}
