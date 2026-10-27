<?php

namespace App\Http\Requests\Central;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCentralPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $pageId = $this->route('page');

        return [
            'title' => [
                'sometimes',
                'string',
                'max:255',
            ],

            'slug' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('central_pages', 'slug')->ignore($pageId),
            ],

            'content' => [
                'sometimes',
                'string',
            ],
            'hero_title' => ['nullable', 'string', 'max:255'],
            'hero_subtitle' => ['nullable', 'string', 'max:2000'],
            'hero_image' => ['nullable', 'string', 'max:2048'],
            'hero_cta_label' => ['nullable', 'string', 'max:100'],
            'hero_cta_url' => ['nullable', 'string', 'max:2048'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],

            'meta_title' => [
                'nullable',
                'string',
                'max:255',
            ],

            'meta_description' => [
                'nullable',
                'string',
                'max:500',
            ],

            'is_published' => [
                'nullable',
                'boolean',
            ],

            'published_at' => [
                'nullable',
                'date',
            ],
        ];
    }
}
