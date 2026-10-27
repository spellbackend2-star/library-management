<?php

namespace App\Http\Requests\Central;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCentralBlogRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $blog = $this->route('blog');

        return [
            'category_id' => ['sometimes', 'nullable', 'integer', Rule::exists('central_blog_categories', 'id')->where('is_active', true)],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'required', 'string', 'max:255', 'alpha_dash', Rule::unique('central_blogs', 'slug')->ignore($blog?->id)],
            'excerpt' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'content' => ['sometimes', 'required', 'string'],
            'featured_image' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'meta_title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'meta_description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'is_published' => ['sometimes', 'boolean'],
            'published_at' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
