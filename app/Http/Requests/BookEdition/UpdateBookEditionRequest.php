<?php

namespace App\Http\Requests\BookEdition;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookEditionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $editionId = $this->route('book_edition');

        return [
            'book_id' => [
                'sometimes',
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
                Rule::unique('book_editions', 'isbn')->ignore($editionId),
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
