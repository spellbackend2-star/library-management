<?php

namespace App\Http\Requests\Fine;

use Illuminate\Foundation\Http\FormRequest;

class CancelFineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'note' => ['required', 'string', 'max:2000'],
        ];
    }
}
