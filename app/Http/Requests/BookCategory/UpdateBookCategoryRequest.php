<?php

namespace App\Http\Requests\BookCategory;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBookCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'book_id' => [
                'sometimes',
                'integer',
                'exists:books,id',
            ],

            'category_id' => [
                'sometimes',
                'integer',
                'exists:categories,id',
            ],
        ];
    }
}
