<?php

namespace App\Http\Requests\Book;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $bookId = $this->route('book');

        if (is_object($bookId)) {
            $bookId = $bookId->id;
        }

        return [

            /*
            |--------------------------------------------------------------------------
            | Book
            |--------------------------------------------------------------------------
            */

            'title' => [
                'required',
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

            /*
            |--------------------------------------------------------------------------
            | Authors (required, at least one valid author)
            |--------------------------------------------------------------------------
            */

            'author_ids' => [
                'required',
                'array',
                'min:1',
            ],

            'author_ids.*' => [
                'integer',
                'exists:authors,id',
                'distinct',
            ],

            /*
            |--------------------------------------------------------------------------
            | Categories (required, at least one valid category)
            |--------------------------------------------------------------------------
            */

            'category_ids' => [
                'required',
                'array',
                'min:1',
            ],

            'category_ids.*' => [
                'integer',
                'exists:categories,id',
                'distinct',
            ],

            /*
            |--------------------------------------------------------------------------
            | Editions
            |--------------------------------------------------------------------------
            */

            'editions' => [
                'nullable',
                'array',
            ],

            'editions.*' => [
                'required',
                'array',
            ],

            'editions.*.id' => [
                'nullable',
                'integer',
                Rule::exists('book_editions', 'id')
                    ->where(function ($query) use ($bookId) {
                        $query->where('book_id', $bookId);
                    }),
            ],

            'editions.*.publisher_id' => [
                'required',
                'integer',
                'exists:publishers,id',
            ],

            'editions.*.isbn' => [
                'nullable',
                'string',
                'max:20',
            ],

            'editions.*.edition_number' => [
                'nullable',
                'string',
                'max:50',
            ],

            'editions.*.publication_year' => [
                'nullable',
                'integer',
                'min:1000',
                'max:9999',
            ],

            'editions.*.format' => [
                'nullable',
                'in:physical,ebook,audiobook',
            ],

            /*
            |--------------------------------------------------------------------------
            | Copies
            |--------------------------------------------------------------------------
            */

            'editions.*.copies' => [
                'nullable',
                'array',
            ],

            'editions.*.copies.*' => [
                'required',
                'array',
            ],

            'editions.*.copies.*.id' => [
                'nullable',
                'integer',
                'exists:copies,id',
            ],

            'editions.*.copies.*.barcode' => [
                'required',
                'string',
                'max:100',
            ],

            'editions.*.copies.*.shelf_location' => [
                'nullable',
                'string',
                'max:100',
            ],

            'editions.*.copies.*.condition' => [
                'nullable',
                'in:new,good,fair,damaged',
            ],

            'editions.*.copies.*.status' => [
                'nullable',
                'in:available,on_loan,reserved,withdrawn',
            ],

            'editions.*.copies.*.acquisition_date' => [
                'nullable',
                'date',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Book title is required.',
            'author_ids.required' => 'At least one author is required to update a book.',
            'author_ids.array' => 'author_ids must be an array.',
            'author_ids.min' => 'At least one author is required to update a book.',
            'author_ids.*.exists' => 'One or more author_ids do not exist in the authors table.',
            'category_ids.required' => 'At least one category is required to update a book.',
            'category_ids.array' => 'category_ids must be an array.',
            'category_ids.min' => 'At least one category is required to update a book.',
            'category_ids.*.exists' => 'One or more category_ids do not exist in the categories table.',
            'editions.*.publisher_id.required' => 'Each edition must have a publisher_id.',
            'editions.*.publisher_id.exists' => 'The selected publisher_id does not exist.',
            'editions.*.copies.*.barcode.required' => 'Each copy must have a barcode.',
            'editions.*.copies.*.barcode.unique' => 'The barcode :input has already been taken.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation failed.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
