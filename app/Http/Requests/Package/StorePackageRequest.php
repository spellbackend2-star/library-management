<?php

namespace App\Http\Requests\Package;

use Illuminate\Foundation\Http\FormRequest;

class StorePackageRequest extends FormRequest
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
                'unique:packages,name',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'duration' => [
                'required',
                'integer',
                'min:1',
            ],

            'duration_unit' => [
                'required',
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
