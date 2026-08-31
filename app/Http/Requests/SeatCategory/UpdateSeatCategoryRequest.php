<?php

namespace App\Http\Requests\SeatCategory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSeatCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = $this->route('seat_category');

        return [
            'name' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('seat_categories', 'name')->ignore($categoryId),
            ],

            'description' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }
}
