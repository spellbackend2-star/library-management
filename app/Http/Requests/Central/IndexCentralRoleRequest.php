<?php

namespace App\Http\Requests\Central;

use Illuminate\Foundation\Http\FormRequest;

class IndexCentralRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('api')?->can('roles.view') ?? false;
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
