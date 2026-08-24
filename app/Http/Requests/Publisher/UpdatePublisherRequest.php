<?php

namespace App\Http\Requests\Publisher;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePublisherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $publisherId = $this->route('publisher');

        return [
            'name' => [
                'sometimes',
                'string',
                'max:255',
            ],

            'address' => [
                'nullable',
                'string',
                'max:255',
            ],

            'website' => [
                'nullable',
                'string',
                'max:500',
            ],

            'contact_email' => [
                'nullable',
                'email',
                'max:255',
            ],
        ];
    }
}
