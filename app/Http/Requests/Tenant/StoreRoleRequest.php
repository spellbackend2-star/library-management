<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('api');

        return $user && ($user->hasRole('admin', 'api') || $user->can('roles.create'));
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('roles', 'name')->where('guard_name', 'api')],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => [
                'required', 'string', 'distinct',
                Rule::exists('permissions', 'name')->where('guard_name', 'api'),
                function (string $attribute, mixed $value, $fail): void {
                    if (! $this->user('api')?->can((string) $value)) {
                        $fail('You cannot assign a permission you do not have.');
                    }
                },
            ],
        ];
    }
}
