<?php

namespace App\Http\Requests\Package;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $packageId = $this->route('package');

        return [
            'name' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('packages', 'name')->ignore($packageId),
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'price' => [
                'sometimes',
                'numeric',
                'min:0',
            ],

            'duration' => [
                'sometimes',
                'integer',
                'min:1',
            ],

            'duration_unit' => [
                'sometimes',
                'string',
                'in:day,month,year',
            ],

            'max_book_loans' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'max_borrow_days' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'seat_access_allowed' => [
                'nullable',
                'boolean',
            ],

            'max_seat_hours_per_day' => [
                'nullable',
                'numeric',
                'min:0',
                'max:24',
            ],

            'locker_allowed' => [
                'nullable',
                'boolean',
            ],

            'locker_type' => [
                'nullable',
                'string',
                'max:50',
            ],

            'max_locker_hours_per_day' => [
                'nullable',
                'numeric',
                'min:0',
                'max:24',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],
        ];
    }
}
