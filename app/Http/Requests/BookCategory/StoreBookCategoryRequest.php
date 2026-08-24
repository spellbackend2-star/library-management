<?php

namespace App\Http\Requests\BookCategory;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'book_id' => [
                'required',
                'integer',
                'exists:books,id',
            ],

            'category_id' => [
                'required',
                'integer',
                'exists:categories,id',
            ],
        ];
    }
}
