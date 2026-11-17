<?php

namespace App\Http\Requests\Setting;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBookFineRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rate_per_day' => ['required', 'numeric', 'min:0', 'max:999999.99'],
        ];
    }
}
