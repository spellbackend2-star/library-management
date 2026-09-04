<?php

namespace App\Http\Requests\Book;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class AddCopyToBookRequest extends FormRequest
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
            'edition_id' => [
                'required',
                'integer',
                Rule::exists('book_editions', 'id')
                    ->where(function ($query) use ($bookId) {
                        $query->where('book_id', $bookId);
                    }),
            ],

            'copies' => [
                'required',
                'array',
                'min:1',
            ],

            'copies.*.barcode' => [
                'required',
                'string',
                'max:100',
                'distinct',
                'unique:copies,barcode',
            ],

            'copies.*.shelf_location' => [
                'nullable',
                'string',
                'max:100',
            ],

            'copies.*.condition' => [
                'nullable',
                'in:new,good,fair,damaged',
            ],

            'copies.*.status' => [
                'nullable',
                'in:available,on_loan,reserved,withdrawn',
            ],

            'copies.*.acquisition_date' => [
                'nullable',
                'date',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'edition_id.required' => 'edition_id is required.',
            'edition_id.exists' => 'The selected edition_id does not belong to this book.',
            'copies.required' => 'At least one copy is required.',
            'copies.array' => 'copies must be an array.',
            'copies.min' => 'At least one copy is required.',
            'copies.*.barcode.required' => 'Each copy must have a barcode.',
            'copies.*.barcode.unique' => 'The barcode :input has already been taken.',
            'copies.*.barcode.distinct' => 'Duplicate barcodes are not allowed in the same request.',
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
