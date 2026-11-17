<?php

namespace App\Http\Requests\Fine;

use Illuminate\Foundation\Http\FormRequest;

class FineIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'all' => ['sometimes', 'boolean'],
            'member_id' => ['sometimes', 'integer'],
            'staff_id' => ['sometimes', 'integer'],
            'status' => ['sometimes', 'string'],
            'invoice_id' => ['sometimes', 'integer'],
        ];
    }
}
