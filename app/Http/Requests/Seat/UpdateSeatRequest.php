<?php

namespace App\Http\Requests\Seat;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSeatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $seatId = $this->route('seat');

        return [
            'room_id' => [
                'sometimes',
                'integer',
                'exists:rooms,id',
            ],

            'category_id' => [
                'sometimes',
                'integer',
                'exists:seat_categories,id',
            ],

            'seat_number' => [
                'sometimes',
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
