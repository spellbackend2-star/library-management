<?php

namespace App\Http\Requests\Copy;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCopyRequest extends FormRequest
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

            'barcode' => [
                'required',
                'string',
                'max:100',
                'unique:copies,barcode',
            ],

            'shelf_location' => [
                'nullable',
                'string',
                'max:100',
            ],

            'condition' => [
                'nullable',
                'in:new,good,fair,damaged',
            ],

            'status' => [
                'nullable',
                'in:available,on_loan,reserved,withdrawn',
            ],

            'acquisition_date' => [
                'nullable',
                'date',
            ],
        ];
    }
}
