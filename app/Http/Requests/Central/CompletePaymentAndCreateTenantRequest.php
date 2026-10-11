<?php

namespace App\Http\Requests\Central;

use App\Models\CentralInvoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompletePaymentAndCreateTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $invoice = $this->route('invoice');
        $tenantId = $invoice instanceof CentralInvoice
            ? $invoice->tenant_id
            : null;

        $phoneRule = Rule::unique('tenants', 'phone');
        $emailRule = Rule::unique('tenants', 'owner_email');
        $subdomainRule = Rule::unique('tenants', 'tenant_code');

        if ($tenantId) {
            $phoneRule->ignore($tenantId);
            $emailRule->ignore($tenantId);
            $subdomainRule->ignore($tenantId);
        }

        return [
            'owner' => [
                'required',
                'string',
                'max:255',
            ],
            'company_name' => [
                'required',
                'string',
                'max:255',
            ],
            'phone' => [
                'required',
                'string',
                'max:50',
                $phoneRule,
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                $emailRule,
            ],
            'password' => [
                'required',
                'string',
                'min:8',
            ],
            'subdomain' => [
                'required',
                'string',
                'max:255',
                $subdomainRule,
            ],
        ];
    }
}
