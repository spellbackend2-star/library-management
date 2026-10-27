<?php

namespace App\Http\Requests\Central;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Rules\AssignableCentralRole;

class UpdateCentralStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('api')?->can('users.update') ?? false;
    }

    public function rules(): array
    {
        $userId = $this->route('staff');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => [
                'sometimes',
                'string',
                'min:8',
                'confirmed',
            ],
            'role' => [
                'sometimes',
                'string',
                Rule::exists('roles', 'name')->where('guard_name', 'api'),
                new AssignableCentralRole(),
            ],
        ];
    }
}
