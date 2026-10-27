<?php

namespace App\Http\Requests\Central;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Rules\AssignableCentralRole;

class AssignCentralStaffRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('api')?->can('users.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'role' => [
                'required',
                'string',
                Rule::exists('roles', 'name')->where('guard_name', 'api'),
                new AssignableCentralRole(),
            ],
        ];
    }
}
