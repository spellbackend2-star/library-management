<?php

namespace App\Http\Requests\BookEdition;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookEditionRequest extends FormRequest
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

            'publisher_id' => [
                'nullable',
                'integer',
                'exists:publishers,id',
            ],

            'isbn' => [
                'nullable',
                'string',
                'max:20',
                'unique:book_editions,isbn',
            ],

            'edition_number' => [
                'nullable',
                'string',
                'max:50',
            ],

            'publication_year' => [
                'nullable',
                'integer',
            ],

            'format' => [
                'nullable',
                'string',
                'max:20',
            ],
        ];
    }
}
