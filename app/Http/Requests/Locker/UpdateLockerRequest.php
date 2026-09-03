<?php

namespace App\Http\Requests\Locker;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLockerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $lockerId = $this->route('locker');

        return [
            'floor_id' => [
                'sometimes',
                'integer',
                'exists:floors,id',
            ],

            'locker_number' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('lockers', 'locker_number')->ignore($lockerId),
            ],

            'locker_type' => [
                'sometimes',
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
