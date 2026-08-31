<?php

namespace App\Http\Requests\Borrow;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBorrowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $borrowId = $this->route('borrow');

        return [
            'copy_id' => [
                'sometimes',
                'integer',
                'exists:copies,id',
            ],

            'member_id' => [
                'sometimes',
                'integer',
                'exists:members,id',
            ],

            'staff_id' => [
                'nullable',
                'integer',
                'exists:staff,id',
            ],

            'due_date' => [
                'sometimes',
                'date',
            ],

            'return_date' => [
                'nullable',
                'date',
            ],

            'renewal_count' => [
                'sometimes',
                'integer',
                'min:0',
            ],

            'status' => [
                'sometimes',
                'in:active,returned,overdue,lost',
            ],
        ];
    }
}
