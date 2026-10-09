<?php

namespace App\Http\Requests\Central;

use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CentralTenantUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenant = $this->route('tenant');
        $tenantId = $tenant instanceof Tenant ? $tenant->getKey() : $tenant;

        return [
            'company_name' => [
                'sometimes',
                'string',
                'max:255',
            ],

            'phone' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
            ],

            'owner_email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('tenants', 'owner_email')->ignore($tenantId),
            ],

            'owner_name' => [
                'sometimes',
                'string',
                'max:255',
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if (! $this->hasAny([
                'company_name',
                'owner_name',
                'owner_email',
                'phone',
            ])) {
                $validator->errors()->add(
                    'tenant',
                    'Provide at least one tenant profile field to update.'
                );
            }
        });
    }
}
