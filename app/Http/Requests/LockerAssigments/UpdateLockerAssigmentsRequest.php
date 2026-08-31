<?php

namespace App\Http\Requests\LockerAssigments;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLockerAssigmentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $assignmentId = $this->route('locker_assigments');

        return [
            'locker_id' => [
                'sometimes',
                'integer',
                'exists:lockers,id',
            ],

            'member_id' => [
                'sometimes',
                'integer',
                'exists:members,id',
            ],

            'assigned_date' => [
                'sometimes',
                'date',
            ],

            'expiry_date' => [
                'sometimes',
                'date',
                'after_or_equal:assigned_date',
            ],

            'returned_date' => [
                'nullable',
                'date',
            ],

            'status' => [
                'sometimes',
                'in:active,expired,returned,cancelled',
            ],
        ];
    }
}
