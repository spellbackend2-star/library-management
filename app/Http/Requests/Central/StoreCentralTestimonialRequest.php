<?php

namespace App\Http\Requests\Central;

use Illuminate\Foundation\Http\FormRequest;

class StoreCentralTestimonialRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'role_or_company' => ['nullable', 'string', 'max:180'],
            'quote' => ['required', 'string', 'max:3000'],
            'photo' => ['nullable', 'string', 'max:2048'],
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'is_published' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
