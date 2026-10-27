<?php

namespace App\Http\Requests\Central;

use Illuminate\Foundation\Http\FormRequest;

class StoreCentralBlogCategoryRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:140', 'alpha_dash', 'unique:central_blog_categories,slug'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
