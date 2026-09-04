<?php

namespace App\Http\Requests\Book;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateCopyForBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $copyId = $this->route('copy');

        if (is_object($copyId)) {
            $copyId = $copyId->id;
        }

        return [
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

    public function messages(): array
    {
        return [
            'barcode.unique' => 'The barcode :input has already been taken.',
            'condition.in' => 'The condition must be one of: new, good, fair, damaged.',
            'status.in' => 'The status must be one of: available, on_loan, reserved, withdrawn.',
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
