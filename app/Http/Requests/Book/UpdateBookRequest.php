<?php

namespace App\Http\Requests\Book;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Support\Isbn;

class UpdateBookRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $editions = $this->input('editions', []);
        if (! is_array($editions)) {
            return;
        }

        foreach ($editions as &$edition) {
            if (! is_array($edition)) {
                continue;
            }
            if (is_string($edition['isbn'] ?? null)) {
                $edition['isbn'] = Isbn::normalize($edition['isbn']);
            }
            if (is_array($edition['copies'] ?? null)) {
                foreach ($edition['copies'] as &$copy) {
                    if (is_array($copy) && is_string($copy['barcode'] ?? null)) {
                        $copy['barcode'] = trim($copy['barcode']);
                    }
                }
                unset($copy);
            }
        }
        unset($edition);

        $this->merge(['editions' => $editions]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            foreach ((array) $this->input('editions', []) as $index => $edition) {
                if (! is_array($edition)) {
                    continue;
                }

                if (! isset($edition['id']) && empty($edition['publisher_id'])) {
                    $validator->errors()->add(
                        "editions.{$index}.publisher_id",
                        'Each new edition must have a publisher_id.'
                    );
                }

                if (! is_string($edition['isbn'] ?? null) || $edition['isbn'] === '') {
                    continue;
                }

                if (! Isbn::isValid($edition['isbn'])) {
                    $validator->errors()->add(
                        "editions.{$index}.isbn",
                        'The ISBN must be a valid 10-digit ISBN-10 or 13-digit ISBN-13 starting with 978 or 979, with a valid checksum.'
                    );
                }

                $query = DB::table('book_editions')->where('isbn', $edition['isbn']);
                if (isset($edition['id'])) {
                    $query->where('id', '!=', $edition['id']);
                }

                if ($query->exists()) {
                    $validator->errors()->add("editions.{$index}.isbn", 'The ISBN has already been taken.');
                }
            }

            foreach ((array) $this->input('editions', []) as $editionIndex => $edition) {
                if (! is_array($edition) || ! is_array($edition['copies'] ?? null)) {
                    continue;
                }

                foreach ($edition['copies'] as $copyIndex => $copy) {
                    if (! is_array($copy)) {
                        continue;
                    }

                    $copyId = $copy['id'] ?? $copy['copy_id'] ?? null;
                    $hasCopyId = isset($copy['id']) || isset($copy['copy_id']);

                    if (! $hasCopyId && (! is_string($copy['barcode'] ?? null) || trim($copy['barcode']) === '')) {
                        $validator->errors()->add(
                            "editions.{$editionIndex}.copies.{$copyIndex}.barcode",
                            'Each new copy must have a barcode.'
                        );
                    }

                    if (! is_string($copy['barcode'] ?? null) || $copy['barcode'] === '') {
                        continue;
                    }

                    if (is_numeric($copyId)) {
                        $currentBarcode = DB::table('copies')
                            ->where('id', $copyId)
                            ->value('barcode');

                        if (is_string($currentBarcode) && trim($currentBarcode) === $copy['barcode']) {
                            continue;
                        }
                    }

                    $query = DB::table('copies')->where('barcode', $copy['barcode']);
                    if (is_numeric($copyId)) {
                        $query->where('id', '!=', $copyId);
                    }

                    if ($query->exists()) {
                        $validator->errors()->add(
                            "editions.{$editionIndex}.copies.{$copyIndex}.barcode",
                            'The barcode '.$copy['barcode'].' has already been taken.'
                        );
                    }
                }
            }
        });
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

            /*
            |--------------------------------------------------------------------------
            | Authors (required, at least one valid author)
            |--------------------------------------------------------------------------
            */

            'author_ids' => [
                'sometimes',
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
                'sometimes',
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
                'sometimes',
                'nullable',
                'integer',
                'exists:publishers,id',
            ],

            'editions.*.isbn' => [
                'nullable',
                'string',
                'max:20',
                'distinct',
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

            'editions.*.copies.*.copy_id' => [
                'nullable',
                'integer',
                'exists:copies,id',
            ],

            'editions.*.copies.*.barcode' => [
                'sometimes',
                'string',
                'max:100',
                'distinct',
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
            'editions.*.copies.*.barcode.required' => 'Each new copy must have a barcode.',
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
