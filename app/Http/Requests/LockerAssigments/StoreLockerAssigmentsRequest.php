<?php

namespace App\Http\Requests\LockerAssigments;

use Illuminate\Foundation\Http\FormRequest;

class StoreLockerAssigmentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'locker_id' => [
                'required',
                'integer',
                'exists:lockers,id',
            ],

            'member_id' => [
                'required',
                'integer',
                'exists:members,id',
            ],

            'assigned_date' => [
                'required',
                'date',
            ],

            'expiry_date' => [
                'required',
                'date',
                'after_or_equal:assigned_date',
            ],

            'returned_date' => [
                'nullable',
                'date',
            ],

            'status' => [
                'nullable',
                'in:active,expired,returned,cancelled',
            ],
        ];
    }
}
