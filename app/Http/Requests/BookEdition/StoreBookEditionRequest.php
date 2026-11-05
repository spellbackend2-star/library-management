<?php

namespace App\Http\Requests\BookEdition;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\ValidIsbn;
use App\Support\Isbn;

class StoreBookEditionRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('isbn'))) {
            $this->merge(['isbn' => Isbn::normalize($this->input('isbn'))]);
        }
    }

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
                'max:13',
                'unique:book_editions,isbn',
                new ValidIsbn(),
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
