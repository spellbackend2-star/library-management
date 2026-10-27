<?php

namespace App\Http\Requests\Central;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCentralPermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('api')?->can('roles.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z][a-z0-9_-]*(\.[a-z][a-z0-9_-]*)*$/',
                Rule::unique('permissions', 'name')->where('guard_name', 'api'),
            ],
        ];
    }
}
