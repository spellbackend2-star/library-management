<?php

namespace App\Http\Requests\Seat;

use Illuminate\Foundation\Http\FormRequest;

class StoreSeatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'room_id' => [
                'required',
                'integer',
                'exists:rooms,id',
            ],

            'category_id' => [
                'required',
                'integer',
                'exists:seat_categories,id',
            ],

            'seat_number' => [
                'required',
                'string',
                'max:20',
            ],

            'has_power_outlet' => [
                'nullable',
                'boolean',
            ],

            'is_accessible' => [
                'nullable',
                'boolean',
            ],

            'status' => [
                'nullable',
                'in:available,maintenance,out_of_service',
            ],
        ];
    }
}
