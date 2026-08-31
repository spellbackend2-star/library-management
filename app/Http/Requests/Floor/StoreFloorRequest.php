<?php

namespace App\Http\Requests\Floor;

use Illuminate\Foundation\Http\FormRequest;

class StoreFloorRequest extends FormRequest
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
                'unique:floors,name',
            ],

            'floor_number' => [
                'required',
                'integer',
                'unique:floors,floor_number',
            ],

            'description' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }
}
