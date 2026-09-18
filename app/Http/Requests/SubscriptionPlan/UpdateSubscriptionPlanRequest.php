<?php

namespace App\Http\Requests\SubscriptionPlan;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSubscriptionPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('subscription_plan')?->id ?? $this->route('subscription_plan');

        return [
            'name' => [
                'sometimes',
                'string',
                'max:100',
                'unique:subscription_plans,name,' . $id,
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
            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }
}
