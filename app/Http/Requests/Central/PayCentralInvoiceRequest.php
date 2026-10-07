<?php

namespace App\Http\Requests\Central;

use Illuminate\Foundation\Http\FormRequest;

class PayCentralInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('payment_method')) {
            $this->merge([
                'payment_method' => strtoupper((string) $this->input('payment_method')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'invoice_id' => [
                'required',
                'integer',
                'exists:invoices,id',
            ],
            'amount' => [
                'required',
                'numeric',
                'gt:0',
            ],
            'payment_method' => [
                'required',
                'string',
                'in:KHALTI,ESEWA',
            ],
            'return_url' => [
                'nullable',
                'url',
            ],
        ];
    }
}
