<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class IndexRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('api');

        return $user && ($user->hasRole('admin', 'api') || $user->can('roles.view'));
    }

    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'sort_by' => ['sometimes', 'string', 'in:id,name,created_at,updated_at'],
            'sort_order' => ['sometimes', 'string', 'in:asc,desc'],
        ];
    }
}
