<?php

namespace App\Http\Requests\Floor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFloorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $floorId = $this->route('floor');

        return [
            'name' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('floors', 'name')->ignore($floorId),
            ],

            'floor_number' => [
                'sometimes',
                'integer',
                Rule::unique('floors', 'floor_number')->ignore($floorId),
            ],

            'description' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }
}
