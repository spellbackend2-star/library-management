<?php

namespace App\Http\Requests\Book;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => [
                'sometimes',
                'string',
                'max:500',
            ],

            'subtitle' => [
                'nullable',
                'string',
                'max:500',
            ],

            'language' => [
                'nullable',
                'string',
                'max:50',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'cover_image_url' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }
}
