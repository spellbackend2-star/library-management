<?php

namespace App\Services;

use App\Repositories\Interface\CentralSettingInterface;
use InvalidArgumentException;

class CentralSettingService
{
    /**
     * Central settings are stored in the central settings table, one
     * "group" per settings section. Tenant settings use their own
     * groups inside the tenant database and are never touched here.
     */
    public const GENERAL_GROUP = 'general';
    public const WEBSITE_GROUP = 'website';
    public const PAYMENT_GROUP = 'payment';
    public const SMTP_GROUP = 'smtp';
    public const SMS_GROUP = 'sms';
    public const NOTIFICATION_GROUP = 'notification';

    /**
     * General settings.
     */
    public const GENERAL_SETTINGS = [
        'platform_name' => [
            'value' => 'Library Management SaaS',
            'type' => 'string',
            'description' => 'Platform name.',
        ],
        'platform_email' => [
            'value' => null,
            'type' => 'string',
            'description' => 'Platform contact email.',
        ],
        'platform_phone' => [
            'value' => null,
            'type' => 'string',
            'description' => 'Platform contact phone number.',
        ],
        'platform_address' => [
            'value' => null,
            'type' => 'string',
            'description' => 'Platform address.',
        ],
        'platform_website' => [
            'value' => null,
            'type' => 'string',
            'description' => 'Platform website.',
        ],
        'platform_logo' => [
            'value' => null,
            'type' => 'string',
            'description' => 'Platform logo path.',
        ],
        'platform_favicon' => [
            'value' => null,
            'type' => 'string',
            'description' => 'Platform favicon path.',
        ],
        'timezone' => [
            'value' => 'Asia/Kathmandu',
            'type' => 'string',
            'description' => 'Platform timezone.',
        ],
        'currency' => [
            'value' => 'NPR',
            'type' => 'string',
            'description' => 'Platform currency code.',
        ],
        'currency_symbol' => [
            'value' => 'Rs.',
            'type' => 'string',
            'description' => 'Platform currency symbol.',
        ],
        'date_format' => [
            'value' => 'Y-m-d',
            'type' => 'string',
            'description' => 'Platform date format.',
        ],
        'time_format' => [
            'value' => 'H:i',
            'type' => 'string',
            'description' => 'Platform time format.',
        ],
        'maintenance_mode' => [
            'value' => false,
            'type' => 'boolean',
            'description' => 'Whether the platform is in maintenance mode.',
        ],
    ];

    /**
     * Website settings, used for the public landing and marketing pages.
     */
    public const WEBSITE_SETTINGS = [
        'site_name' => [
            'value' => 'Library Management',
            'type' => 'string',
            'description' => 'Website name.',
        ],
        'site_title' => [
            'value' => null,
            'type' => 'string',
            'description' => 'Website browser title.',
        ],
        'site_tagline' => [
            'value' => null,
            'type' => 'string',
            'description' => 'Website tagline.',
        ],
        'site_description' => [
            'value' => null,
            'type' => 'string',
            'description' => 'Website meta description.',
        ],
        'logo' => [
            'value' => null,
            'type' => 'string',
            'description' => 'Website logo path.',
        ],
        'favicon' => [
            'value' => null,
            'type' => 'string',
            'description' => 'Website favicon path.',
        ],
        'contact_email' => [
            'value' => null,
            'type' => 'string',
            'description' => 'Website contact email.',
        ],
        'contact_phone' => [
            'value' => null,
            'type' => 'string',
            'description' => 'Website contact phone number.',
        ],
        'contact_address' => [
            'value' => null,
            'type' => 'string',
            'description' => 'Website contact address.',
        ],
        'website_url' => [
            'value' => null,
            'type' => 'string',
            'description' => 'Website URL.',
        ],
        'support_email' => [
            'value' => null,
            'type' => 'string',
            'description' => 'Website support email.',
        ],
        'support_phone' => [
            'value' => null,
            'type' => 'string',
            'description' => 'Website support phone number.',
        ],
        'facebook_url' => [
            'value' => null,
            'type' => 'string',
            'description' => 'Website facebook URL.',
        ],
        'instagram_url' => [
            'value' => null,
            'type' => 'string',
            'description' => 'Website instagram URL.',
        ],
        'twitter_url' => [
            'value' => null,
            'type' => 'string',
            'description' => 'Website twitter URL.',
        ],
        'linkedin_url' => [
            'value' => null,
            'type' => 'string',
            'description' => 'Website linkedin URL.',
        ],
        'youtube_url' => [
            'value' => null,
            'type' => 'string',
            'description' => 'Website youtube URL.',
        ],
        'terms_url' => [
            'value' => null,
            'type' => 'string',
            'description' => 'Website terms and conditions URL.',
        ],
        'privacy_url' => [
            'value' => null,
            'type' => 'string',
            'description' => 'Website privacy policy URL.',
        ],
        'refund_policy_url' => [
            'value' => null,
            'type' => 'string',
            'description' => 'Website refund policy URL.',
        ],
        'footer_text' => [
            'value' => null,
            'type' => 'string',
            'description' => 'Website footer text.',
        ],
        'copyright_text' => [
            'value' => null,
            'type' => 'string',
            'description' => 'Website copyright text.',
        ],
        'google_analytics_id' => [
            'value' => null,
            'type' => 'string',
            'description' => 'Website google analytics ID.',
        ],
        'google_tag_manager_id' => [
            'value' => null,
            'type' => 'string',
            'description' => 'Website google tag manager ID.',
        ],
    ];

    /**
     * Payment gateway settings.
     */
    public const PAYMENT_SETTINGS = [
        'khalti_enabled' => [
            'value' => false,
            'type' => 'boolean',
            'description' => 'Whether the Khalti gateway is enabled.',
        ],
        'khalti_public_key' => [
            'value' => null,
            'type' => 'string',
            'description' => 'Khalti public key.',
        ],
        'khalti_secret_key' => [
            'value' => null,
            'type' => 'string',
            'description' => 'Khalti secret key.',
        ],
        'khalti_base_url' => [
            'value' => 'https://api.khalti.com',
            'type' => 'string',
            'description' => 'Khalti API base URL.',
        ],
        'esewa_enabled' => [
            'value' => false,
            'type' => 'boolean',
            'description' => 'Whether the eSewa gateway is enabled.',
        ],
        'esewa_merchant_id' => [
            'value' => null,
            'type' => 'string',
            'description' => 'eSewa merchant ID.',
        ],
        'esewa_secret_key' => [
            'value' => null,
            'type' => 'string',
            'description' => 'eSewa secret key.',
        ],
        'esewa_base_url' => [
            'value' => 'https://payment.esewa.com.np',
            'type' => 'string',
            'description' => 'eSewa API base URL.',
        ],
        'default_currency' => [
            'value' => 'NPR',
            'type' => 'string',
            'description' => 'Default currency used for platform payments.',
        ],
        'payment_timeout' => [
            'value' => 900,
            'type' => 'integer',
            'description' => 'Payment session timeout in seconds.',
        ],
    ];

    /**
     * SMTP mail settings.
     */
    public const SMTP_SETTINGS = [
        'smtp_enabled' => [
            'value' => false,
            'type' => 'boolean',
            'description' => 'Whether SMTP mail sending is enabled.',
        ],
        'smtp_host' => [
            'value' => null,
            'type' => 'string',
            'description' => 'SMTP host.',
        ],
        'smtp_port' => [
            'value' => 587,
            'type' => 'integer',
            'description' => 'SMTP port.',
        ],
        'smtp_username' => [
            'value' => null,
            'type' => 'string',
            'description' => 'SMTP username.',
        ],
        'smtp_password' => [
            'value' => null,
            'type' => 'string',
            'description' => 'SMTP password.',
        ],
        'smtp_encryption' => [
            'value' => 'tls',
            'type' => 'string',
            'description' => 'SMTP encryption, e.g. tls or ssl.',
        ],
        'from_email' => [
            'value' => null,
            'type' => 'string',
            'description' => 'Default from email address.',
        ],
        'from_name' => [
            'value' => null,
            'type' => 'string',
            'description' => 'Default from name.',
        ],
    ];

    /**
     * SMS gateway settings.
     */
    public const SMS_SETTINGS = [
        'sms_enabled' => [
            'value' => false,
            'type' => 'boolean',
            'description' => 'Whether SMS sending is enabled.',
        ],
        'sms_provider' => [
            'value' => null,
            'type' => 'string',
            'description' => 'SMS provider name.',
        ],
        'sms_api_url' => [
            'value' => null,
            'type' => 'string',
            'description' => 'SMS provider API URL.',
        ],
        'sms_api_key' => [
            'value' => null,
            'type' => 'string',
            'description' => 'SMS provider API key.',
        ],
        'sms_sender_id' => [
            'value' => null,
            'type' => 'string',
            'description' => 'SMS sender ID.',
        ],
        'sms_campaign_id' => [
            'value' => null,
            'type' => 'string',
            'description' => 'SMS campaign ID.',
        ],
    ];

    /**
     * Platform level notification controls.
     */
    public const NOTIFICATION_SETTINGS = [
        'email_notification_enabled' => [
            'value' => true,
            'type' => 'boolean',
            'description' => 'Whether email notifications are enabled platform wide.',
        ],
        'sms_notification_enabled' => [
            'value' => true,
            'type' => 'boolean',
            'description' => 'Whether SMS notifications are enabled platform wide.',
        ],
        'subscription_notification_enabled' => [
            'value' => true,
            'type' => 'boolean',
            'description' => 'Whether subscription notifications are enabled platform wide.',
        ],
        'payment_notification_enabled' => [
            'value' => true,
            'type' => 'boolean',
            'description' => 'Whether payment notifications are enabled platform wide.',
        ],
        'tenant_notification_enabled' => [
            'value' => true,
            'type' => 'boolean',
            'description' => 'Whether tenant notifications are enabled platform wide.',
        ],
        'invoice_notification_enabled' => [
            'value' => true,
            'type' => 'boolean',
            'description' => 'Whether invoice notifications are enabled platform wide.',
        ],
        'maintenance_notification_enabled' => [
            'value' => true,
            'type' => 'boolean',
            'description' => 'Whether maintenance notifications are enabled platform wide.',
        ],
    ];

    /**
     * Every settings group with its allow listed keys. This is the only
     * place reading and writing central settings is defined.
     */
    public const GROUPS = [
        self::GENERAL_GROUP => self::GENERAL_SETTINGS,
        self::WEBSITE_GROUP => self::WEBSITE_SETTINGS,
        self::PAYMENT_GROUP => self::PAYMENT_SETTINGS,
        self::SMTP_GROUP => self::SMTP_SETTINGS,
        self::SMS_GROUP => self::SMS_SETTINGS,
        self::NOTIFICATION_GROUP => self::NOTIFICATION_SETTINGS,
    ];

    public function __construct(
        protected CentralSettingInterface $centralSettingRepository
    ) {}

    /**
     * Every allow listed settings group name.
     */
    public static function groups(): array
    {
        return array_keys(self::GROUPS);
    }

    /**
     * Whether the given group name is allow listed.
     */
    public static function hasGroup(string $group): bool
    {
        return array_key_exists($group, self::GROUPS);
    }

    /**
     * The allow listed settings for a group.
     */
    public static function definitions(string $group): array
    {
        if (! self::hasGroup($group)) {
            throw new InvalidArgumentException(
                'Unknown central settings group: '.$group
            );
        }

        return self::GROUPS[$group];
    }

    /**
     * All settings of a group, defaults used for missing keys.
     */
    public function getGroup(string $group): array
    {
        $definitions = self::definitions($group);

        $stored = $this->centralSettingRepository
            ->findByGroup($group)
            ->keyBy('key');

        $settings = [];

        foreach ($definitions as $key => $definition) {
            $setting = $stored->get($key);

            $settings[$key] = $setting
                ? $setting->value
                : $definition['value'];
        }

        return $settings;
    }

    /**
     * Update the given settings group. Only allow listed keys are
     * written, every other request key is ignored.
     */
    public function updateGroup(string $group, array $data): array
    {
        $definitions = self::definitions($group);

        foreach ($data as $key => $value) {
            if (! array_key_exists($key, $definitions)) {
                continue;
            }

            $definition = $definitions[$key];

            if ($definition['type'] === 'boolean') {
                $value = $value ? '1' : '0';
            }

            $this->centralSettingRepository->updateOrCreateByKey(
                $group,
                $key,
                $value,
                $definition['type'],
                $definition['description']
            );
        }

        return $this->getGroup($group);
    }

    /**
     * All central general settings, defaults used for missing keys.
     */
    public function general(): array
    {
        return $this->getGroup(self::GENERAL_GROUP);
    }

    /**
     * Update the given central general settings.
     */
    public function updateGeneral(array $data): array
    {
        return $this->updateGroup(self::GENERAL_GROUP, $data);
    }

    /**
     * Read a single allow listed setting from any group. Returns the
     * given default when the key is unknown.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        foreach (self::GROUPS as $group => $definitions) {
            if (! array_key_exists($key, $definitions)) {
                continue;
            }

            $setting = $this->centralSettingRepository
                ->findByGroupAndKey($group, $key);

            return $setting
                ? $setting->value
                : ($definitions[$key]['value'] ?? $default);
        }

        return $default;
    }
}
