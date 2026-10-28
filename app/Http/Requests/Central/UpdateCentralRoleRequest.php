<?php

namespace App\Http\Requests\Central;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCentralRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('api')?->can('roles.update') ?? false;
    }

    public function rules(): array
    {
        $roleId = (int) $this->route('role');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                'alpha_dash',
                Rule::unique('roles', 'name')
                    ->where('guard_name', 'api')
                    ->ignore($roleId),
            ],
            'permissions' => ['sometimes', 'required', 'array', 'min:1'],
            'permissions.*' => [
                'required',
                'string',
                'distinct',
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
