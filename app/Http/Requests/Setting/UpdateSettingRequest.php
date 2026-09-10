<?php

namespace App\Http\Requests\Setting;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'group' => [
                'sometimes',
                'string',
                'max:50',
            ],

            'key' => [
                'sometimes',
                'string',
                'max:100',
                function ($attribute, $value, $fail) {
                    $current = $this->route('setting');

                    $group = $this->input('group') ?? ($current->group ?? 'general');

                    $query = Setting::where('group', $group)
                        ->where('key', $value);

                    if ($current) {
                        $query->where('id', '!=', $current->id);
                    }

                    if ($query->exists()) {
                        $fail("The {$attribute} has already been taken.");
                    }
                },
            ],

            'value' => [
                'nullable',
            ],

            'type' => [
                'sometimes',
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
