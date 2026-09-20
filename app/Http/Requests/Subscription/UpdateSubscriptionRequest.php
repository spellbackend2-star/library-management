<?php

namespace App\Http\Requests\Subscription;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('subscription')?->id ?? $this->route('subscription');

        return [
            'subscription_plan_id' => [
                'sometimes',
                'integer',
                'exists:subscription_plans,id',
            ],
            'starts_at' => [
                'nullable',
                'date',
            ],
            'expires_at' => [
                'nullable',
                'date',
                'after_or_equal:starts_at',
            ],
            'status' => [
                'sometimes',
                'string',
                'in:pending,active,expired,cancelled',
            ],
        ];
    }
}
