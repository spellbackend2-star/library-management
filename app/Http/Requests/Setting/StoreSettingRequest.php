<?php

namespace App\Http\Requests\Setting;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;

class StoreSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'group' => [
                'required',
                'string',
                'max:50',
            ],

            'key' => [
                'required',
                'string',
                'max:100',
                function ($attribute, $value, $fail) {
                    if (
                        Setting::where('group', $this->input('group'))
                            ->where('key', $value)
                            ->exists()
                    ) {
                        $fail("The {$attribute} has already been taken.");
                    }
                },
            ],

            'value' => [
                'nullable',
            ],

            'type' => [
                'required',
                'in:string,integer,boolean,json,text',
            ],

            'description' => [
                'nullable',
                'string',
                'max:255',
            ],

            'is_locked' => [
                'boolean',
            ],
        ];
    }
}
