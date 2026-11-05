<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('api');

        return $user && ($user->hasRole('admin', 'api') || $user->can('roles.create'));
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'regex:/^[a-z][a-z0-9_-]*(\.[a-z][a-z0-9_-]*)*$/', Rule::unique('permissions', 'name')->where('guard_name', 'api')],
        ];
    }
}
