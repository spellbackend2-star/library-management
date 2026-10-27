<?php

namespace App\Http\Requests\Central;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Rules\AssignableCentralRole;

class StoreCentralStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('api')?->can('users.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
            'role' => [
                'required',
                'string',
                Rule::exists('roles', 'name')->where('guard_name', 'api'),
                new AssignableCentralRole(),
            ],
        ];
    }
}
