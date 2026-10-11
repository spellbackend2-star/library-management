<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

class AssignRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role' => [
                'required',
                'string',
                'in:admin,manager,librarian,clerk',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === 'admin' && ! $this->user()?->hasRole('admin', 'api')) {
                        $fail('Only a tenant admin can assign the admin role.');
                    }
                },
            ],
        ];
    }
}
