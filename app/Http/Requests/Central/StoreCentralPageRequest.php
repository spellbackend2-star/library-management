<?php

namespace App\Http\Requests\Central;

use Illuminate\Foundation\Http\FormRequest;

class StoreCentralPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'slug' => [
                'required',
                'string',
                'max:255',
                'unique:central_pages,slug',
            ],

            'content' => [
                'required',
                'string',
            ],
            'hero_title' => ['nullable', 'string', 'max:255'],
            'hero_subtitle' => ['nullable', 'string', 'max:2000'],
            'hero_image' => ['nullable', 'string', 'max:2048'],
            'hero_cta_label' => ['nullable', 'string', 'max:100'],
            'hero_cta_url' => ['nullable', 'string', 'max:2048'],
            'sort_order' => ['nullable', 'integer', 'min:0'],

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
