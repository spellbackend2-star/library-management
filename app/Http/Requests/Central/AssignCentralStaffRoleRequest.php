<?php

namespace App\Http\Requests\Central;

use Illuminate\Foundation\Http\FormRequest;

class AssignCentralStaffRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role' => [
                'required',
                'string',
                'exists:roles,name',
            ],
        ];
    }
}
