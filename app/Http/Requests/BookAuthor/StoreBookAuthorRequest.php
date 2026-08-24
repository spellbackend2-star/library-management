<?php

namespace App\Http\Requests\BookAuthor;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookAuthorRequest extends FormRequest
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

            'author_id' => [
                'required',
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
