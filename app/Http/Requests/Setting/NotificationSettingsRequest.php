<?php

namespace App\Http\Requests\Setting;

use Illuminate\Foundation\Http\FormRequest;

class NotificationSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email_notification_enabled' => ['sometimes', 'boolean'],
            'sms_notification_enabled' => ['sometimes', 'boolean'],
            'invoice_notification_enabled' => ['sometimes', 'boolean'],
            'payment_notification_enabled' => ['sometimes', 'boolean'],
            'booking_notification_enabled' => ['sometimes', 'boolean'],
            'overdue_notification_enabled' => ['sometimes', 'boolean'],
            'fine_notification_enabled' => ['sometimes', 'boolean'],
            'membership_expiry_notification_enabled' => ['sometimes', 'boolean'],
        ];
    }
}