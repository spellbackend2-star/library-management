<?php

namespace App\Http\Requests\Setting;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStaffBookRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rate_per_book' => ['required', 'numeric', 'gt:0', 'max:999999.99'],
        ];
    }
}
