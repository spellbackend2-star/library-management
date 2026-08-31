<?php

namespace App\Http\Requests\SeatCategory;

use Illuminate\Foundation\Http\FormRequest;

class StoreSeatCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
                'unique:seat_categories,name',
            ],

            'description' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }
}
