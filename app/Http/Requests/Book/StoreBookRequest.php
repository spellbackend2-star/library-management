<?php

namespace App\Http\Requests\Book;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
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
            | Authors
            |--------------------------------------------------------------------------
            */

            'author_ids' => [
                'nullable',
                'array',
            ],

            'author_ids.*' => [
                'integer',
                'exists:authors,id',
                'distinct',
            ],

            /*
            |--------------------------------------------------------------------------
            | Categories
            |--------------------------------------------------------------------------
            */

            'category_ids' => [
                'nullable',
                'array',
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
            'editions.*.copies.*.barcode' => [
                'required',
                'string',
                'max:100',
                'distinct',
                'unique:copies,barcode',
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

            'editions.*.copies.*.barcode' => [
                'required',
                'string',
                'max:100',
                'distinct',
                'unique:copies,barcode',
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
}
