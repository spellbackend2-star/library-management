<?php

namespace App\Http\Requests\SubscriptionPlan;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubscriptionPlanRequest extends FormRequest
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
                'unique:subscription_plans,name',
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
            'is_active' => [
                'nullable',
                'boolean',
            ],
        ];
    }
}
