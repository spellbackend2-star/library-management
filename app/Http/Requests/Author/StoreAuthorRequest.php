<?php

namespace App\Http\Requests\Author;

use Illuminate\Foundation\Http\FormRequest;

class StoreAuthorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => [
                'required',
                'string',
                'max:100',
            ],

            'last_name' => [
                'required',
                'string',
                'max:100',
            ],

            'bio' => [
                'nullable',
                'string',
            ],

            'birth_date' => [
                'nullable',
                'date',
            ],

            'death_date' => [
                'nullable',
                'date',
            ],
        ];
    }
}
