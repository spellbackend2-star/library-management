<?php

namespace App\Http\Requests\Central;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCentralTestimonialRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:160'],
            'role_or_company' => ['sometimes', 'nullable', 'string', 'max:180'],
            'quote' => ['sometimes', 'required', 'string', 'max:3000'],
            'photo' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'rating' => ['sometimes', 'nullable', 'integer', 'between:1,5'],
            'is_published' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
