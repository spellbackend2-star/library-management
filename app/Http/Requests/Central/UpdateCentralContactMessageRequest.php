<?php

namespace App\Http\Requests\Central;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCentralContactMessageRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return ['status' => ['required', 'in:new,read,replied,archived']];
    }
}
