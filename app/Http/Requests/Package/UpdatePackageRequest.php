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
                Rule::unique('packages', 'name')
                    ->where('is_active', true)
                    ->whereNull('deleted_at')
                    ->ignore($packageId),
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
            if ($this->exists('max_book_loans') || $this->exists('max_borrow_days')) {
                $package = $this->route('package');
                $currentMaxBookLoans = is_object($package)
                    ? $package->max_book_loans
                    : \App\Models\Package::find($package)?->max_book_loans;
                $currentMaxBorrowDays = is_object($package)
                    ? $package->max_borrow_days
                    : \App\Models\Package::find($package)?->max_borrow_days;

                $maxBookLoans = $this->exists('max_book_loans')
                    ? $this->input('max_book_loans')
                    : $currentMaxBookLoans;
                $maxBorrowDays = $this->exists('max_borrow_days')
                    ? $this->input('max_borrow_days')
                    : $currentMaxBorrowDays;

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
