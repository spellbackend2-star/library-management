<?php

namespace App\Http\Requests\Package;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
                Rule::unique('packages', 'name')
                    ->where('is_active', true)
                    ->whereNull('deleted_at'),
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
                'required_if:seat_access_allowed,true',
                'integer',
                'min:1',
                'max:4',
            ],

            'locker_allowed' => [
                'nullable',
                'boolean',
            ],

            'locker_type' => [
                'nullable',
                'required_if:locker_allowed,true',
                'string',
                'max:50',
                Rule::exists('lockers', 'locker_type'),
            ],

            'max_locker_hours_per_day' => [
                'nullable',
                'required_if:locker_allowed,true',
                'integer',
                'min:1',
                'max:4',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $maxBookLoans = $this->input('max_book_loans');
            $maxBorrowDays = $this->input('max_borrow_days');

            if (
                is_numeric($maxBookLoans)
                && (int) $maxBookLoans > 0
                && (! is_numeric($maxBorrowDays) || (int) $maxBorrowDays <= 0)
            ) {
                $validator->errors()->add(
                    'max_borrow_days',
                    'The max borrow days must be greater than 0 when book loans are enabled.'
                );
            }

            $seatAccessAllowed = $this->boolean('seat_access_allowed');
            $seatHours = $this->input('max_seat_hours_per_day');

            if (! $seatAccessAllowed && $seatHours !== null) {
                $validator->errors()->add(
                    'max_seat_hours_per_day',
                    'The max seat hours per day must be null when seat access is disabled.'
                );
            }

            $lockerAllowed = $this->boolean('locker_allowed');
            $lockerType = $this->input('locker_type');
            $lockerHours = $this->input('max_locker_hours_per_day');

            if (! $lockerAllowed) {
                if ($lockerType !== null) {
                    $validator->errors()->add(
                        'locker_type',
                        'The locker type must be null when locker access is disabled.'
                    );
                }
                if ($lockerHours !== null) {
                    $validator->errors()->add(
                        'max_locker_hours_per_day',
                        'The max locker hours per day must be null when locker access is disabled.'
                    );
                }
            }
        });
    }
}
