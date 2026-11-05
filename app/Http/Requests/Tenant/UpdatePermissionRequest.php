<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('api');

        return $user && ($user->hasRole('admin', 'api') || $user->can('roles.update'));
    }

    public function rules(): array
    {
        $permissionId = (int) $this->route('permission');

        return [
            'name' => ['required', 'string', 'max:100', 'regex:/^[a-z][a-z0-9_-]*(\.[a-z][a-z0-9_-]*)*$/', Rule::unique('permissions', 'name')->where('guard_name', 'api')->ignore($permissionId)],
        ];
    }
}
