<?php

namespace App\Http\Requests\Borrow;

use Illuminate\Foundation\Http\FormRequest;

class StoreBorrowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'copy_id' => [
                'required',
                'integer',
                'exists:copies,id',
            ],

            'member_id' => [
                'required',
                'integer',
                'exists:members,id',
            ],

            'staff_id' => [
                'nullable',
                'integer',
                'exists:staff,id',
            ],

            'due_date' => [
                'required',
                'date',
            ],

            'renewal_count' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'status' => [
                'nullable',
                'in:active,returned,overdue,lost',
            ],
        ];
    }
}
