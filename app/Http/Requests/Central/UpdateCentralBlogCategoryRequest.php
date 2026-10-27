<?php

namespace App\Http\Requests\Central;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCentralBlogCategoryRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $category = $this->route('blogCategory');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'slug' => ['sometimes', 'required', 'string', 'max:140', 'alpha_dash', Rule::unique('central_blog_categories', 'slug')->ignore($category?->id)],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
