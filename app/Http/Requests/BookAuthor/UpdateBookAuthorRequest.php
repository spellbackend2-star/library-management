<?php

namespace App\Http\Requests\BookAuthor;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBookAuthorRequest extends FormRequest
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

            'author_id' => [
                'sometimes',
                'integer',
                'exists:authors,id',
            ],

            'author_role' => [
                'nullable',
                'string',
                'max:30',
            ],
        ];
    }
}
