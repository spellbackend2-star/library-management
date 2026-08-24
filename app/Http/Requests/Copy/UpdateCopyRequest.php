<?php

namespace App\Http\Requests\Copy;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCopyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $copyId = $this->route('copy');

        return [
            'book_id' => [
                'sometimes',
                'integer',
                'exists:books,id',
            ],

            'barcode' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('copies', 'barcode')->ignore($copyId),
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
