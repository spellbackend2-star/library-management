<?php

namespace App\Http\Requests\Central;

use App\Services\CentralSettingService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCentralSettingGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules per central settings group. Every key is
     * optional, so only the settings that are sent get written.
     */
    public function rules(): array
    {
        $group = $this->group();

        return match ($group) {
            CentralSettingService::GENERAL_GROUP => [
                'platform_name' => ['sometimes', 'nullable', 'string', 'max:255'],
                'platform_email' => ['sometimes', 'nullable', 'email', 'max:255'],
                'platform_phone' => ['sometimes', 'nullable', 'string', 'max:50'],
                'platform_address' => ['sometimes', 'nullable', 'string', 'max:500'],
                'platform_website' => ['sometimes', 'nullable', 'url', 'max:255'],
                'platform_logo' => ['sometimes', 'nullable', 'string', 'max:500'],
                'platform_favicon' => ['sometimes', 'nullable', 'string', 'max:500'],
                'timezone' => ['sometimes', 'nullable', 'string', 'max:100', 'timezone:all'],
                'currency' => ['sometimes', 'nullable', 'string', 'max:10'],
                'currency_symbol' => ['sometimes', 'nullable', 'string', 'max:20'],
                'date_format' => ['sometimes', 'nullable', 'string', 'max:50'],
                'time_format' => ['sometimes', 'nullable', 'string', 'max:50'],
                'maintenance_mode' => ['sometimes', 'boolean'],
            ],
            CentralSettingService::WEBSITE_GROUP => [
                'site_name' => ['sometimes', 'nullable', 'string', 'max:255'],
                'site_title' => ['sometimes', 'nullable', 'string', 'max:255'],
                'site_tagline' => ['sometimes', 'nullable', 'string', 'max:255'],
                'site_description' => ['sometimes', 'nullable', 'string', 'max:1000'],
                'logo' => ['sometimes', 'nullable', 'string', 'max:500'],
                'favicon' => ['sometimes', 'nullable', 'string', 'max:500'],
                'contact_email' => ['sometimes', 'nullable', 'email', 'max:255'],
                'contact_phone' => ['sometimes', 'nullable', 'string', 'max:50'],
                'contact_address' => ['sometimes', 'nullable', 'string', 'max:500'],
                'website_url' => ['sometimes', 'nullable', 'url', 'max:255'],
                'support_email' => ['sometimes', 'nullable', 'email', 'max:255'],
                'support_phone' => ['sometimes', 'nullable', 'string', 'max:50'],
                'facebook_url' => ['sometimes', 'nullable', 'url', 'max:255'],
                'instagram_url' => ['sometimes', 'nullable', 'url', 'max:255'],
                'twitter_url' => ['sometimes', 'nullable', 'url', 'max:255'],
                'linkedin_url' => ['sometimes', 'nullable', 'url', 'max:255'],
                'youtube_url' => ['sometimes', 'nullable', 'url', 'max:255'],
                'terms_url' => ['sometimes', 'nullable', 'url', 'max:255'],
                'privacy_url' => ['sometimes', 'nullable', 'url', 'max:255'],
                'refund_policy_url' => ['sometimes', 'nullable', 'url', 'max:255'],
                'footer_text' => ['sometimes', 'nullable', 'string', 'max:1000'],
                'copyright_text' => ['sometimes', 'nullable', 'string', 'max:500'],
                'google_analytics_id' => ['sometimes', 'nullable', 'string', 'max:100'],
                'google_tag_manager_id' => ['sometimes', 'nullable', 'string', 'max:100'],
            ],
            CentralSettingService::PAYMENT_GROUP => [
                'khalti_enabled' => ['sometimes', 'boolean'],
                'khalti_public_key' => ['sometimes', 'nullable', 'string', 'max:255'],
                'khalti_secret_key' => ['sometimes', 'nullable', 'string', 'max:255'],
                'khalti_base_url' => ['sometimes', 'nullable', 'url', 'max:255'],
                'esewa_enabled' => ['sometimes', 'boolean'],
                'esewa_merchant_id' => ['sometimes', 'nullable', 'string', 'max:255'],
                'esewa_secret_key' => ['sometimes', 'nullable', 'string', 'max:255'],
                'esewa_base_url' => ['sometimes', 'nullable', 'url', 'max:255'],
                'default_currency' => ['sometimes', 'nullable', 'string', 'max:10'],
                'payment_timeout' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:86400'],
            ],
            CentralSettingService::SMTP_GROUP => [
                'smtp_enabled' => ['sometimes', 'boolean'],
                'smtp_host' => ['sometimes', 'nullable', 'string', 'max:255'],
                'smtp_port' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:65535'],
                'smtp_username' => ['sometimes', 'nullable', 'string', 'max:255'],
                'smtp_password' => ['sometimes', 'nullable', 'string', 'max:255'],
                'smtp_encryption' => ['sometimes', 'nullable', Rule::in(['tls', 'ssl', 'null'])],
                'from_email' => ['sometimes', 'nullable', 'email', 'max:255'],
                'from_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            ],
            CentralSettingService::SMS_GROUP => [
                'sms_enabled' => ['sometimes', 'boolean'],
                'sms_provider' => ['sometimes', 'nullable', 'string', 'max:100'],
                'sms_api_url' => ['sometimes', 'nullable', 'url', 'max:255'],
                'sms_api_key' => ['sometimes', 'nullable', 'string', 'max:255'],
                'sms_sender_id' => ['sometimes', 'nullable', 'string', 'max:100'],
                'sms_campaign_id' => ['sometimes', 'nullable', 'string', 'max:100'],
            ],
            CentralSettingService::NOTIFICATION_GROUP => [
                'email_notification_enabled' => ['sometimes', 'boolean'],
                'sms_notification_enabled' => ['sometimes', 'boolean'],
                'subscription_notification_enabled' => ['sometimes', 'boolean'],
                'payment_notification_enabled' => ['sometimes', 'boolean'],
                'tenant_notification_enabled' => ['sometimes', 'boolean'],
                'invoice_notification_enabled' => ['sometimes', 'boolean'],
                'maintenance_notification_enabled' => ['sometimes', 'boolean'],
            ],
            default => [],
        };
    }

    /**
     * The settings group taken from the route.
     */
    public function group(): string
    {
        return (string) $this->route('group');
    }
}
