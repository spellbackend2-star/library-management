<?php

namespace App\Http\Requests\Fine;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFineAmountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
            'reason' => ['required', 'string', 'max:255'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000']
           
        ];
    }
}
