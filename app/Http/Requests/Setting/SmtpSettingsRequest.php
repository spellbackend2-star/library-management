<?php

namespace App\Http\Requests\Setting;

use Illuminate\Foundation\Http\FormRequest;

class SmtpSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'smtp_host' => ['sometimes', 'nullable', 'string', 'max:255'],
            'smtp_port' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:65535'],
            'smtp_username' => ['sometimes', 'nullable', 'string', 'max:255', 'email'],
            'smtp_password' => ['sometimes', 'nullable', 'string', 'max:255'],
            'smtp_encryption' => ['sometimes', 'nullable', 'in:tls,ssl,none'],
            'smtp_from_address' => ['sometimes', 'nullable', 'string', 'max:255', 'email'],
            'smtp_from_name' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}