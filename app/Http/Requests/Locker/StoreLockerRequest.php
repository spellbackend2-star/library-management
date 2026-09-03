<?php

namespace App\Http\Requests\Locker;
use Illuminate\Validation\Rule;

use Illuminate\Foundation\Http\FormRequest;

class StoreLockerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'floor_id' => [
                'required',
                'integer',
                'exists:floors,id',
            ],

            'locker_number' => [
                'required',
                'string',
                'max:50',
                'unique:lockers,locker_number',
            ],

            'locker_type' => [
                'required',
                'string',
                Rule::in(['small', 'medium', 'big']),
            ],
            'location' => [
                'nullable',
                'string',
                'max:100',
            ],

            'status' => [
                'nullable',
                'in:available,assigned,maintenance,out_of_service',
            ],
        ];
    }
}
