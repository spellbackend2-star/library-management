<?php

namespace App\Http\Requests\Fine;

use Illuminate\Foundation\Http\FormRequest;

class StoreManualFineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'member_id' => ['required', 'integer', 'exists:members,id'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
            'reason' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
