<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Setting\StoreSettingRequest;
use App\Http\Requests\Setting\UpdateSettingRequest;
use App\Http\Requests\Setting\NotificationSettingsRequest;
use App\Http\Requests\Setting\SmtpSettingsRequest;
use App\Http\Resources\SettingResource;
use App\Models\Setting;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SettingController extends Controller
{
    public function __construct(
        protected SettingService $settingService
    ) {}

    private function ensureSettingsTableExists(): ?JsonResponse
    {
        if (! Schema::hasTable('settings')) {
            return response()->json([
                'message' => 'Tenant settings table is not ready. Run the tenant migration first.',
            ], 500);
        }

        return null;
    }

    private function writeSetting(string $group, string $key, mixed $value, string $description): mixed
    {
        if ($key === 'invoice_tax_enabled') {
            $value = $value ? '1' : '0';
        }

        $setting = Setting::updateOrCreate(
            [
                'group' => $group,
                'key' => $key,
            ],
            [
                'group' => $group,
                'key' => $key,
                'value' => $value,
                'type' => 'string',
                'description' => $description,
                'is_locked' => false,
            ]
        );

        return $setting->fresh()->value;
    }

    private function getStoredSetting(string $group, string $key, mixed $default = null): mixed
    {
        $setting = Setting::where('group', $group)
            ->where('key', $key)
            ->first();

        if (! $setting) {
            return $default;
        }

        if ($key === 'invoice_tax_enabled') {
            return filter_var($setting->value, FILTER_VALIDATE_BOOLEAN);
        }

        if (in_array($key, ['invoice_start_number', 'invoice_tax_percentage', 'invoice_due_days'], true)) {
            return (int) $setting->value;
        }

        return $setting->value;
    }

    private function storeImageIfUploaded(Request $request, string $fieldName): ?string
    {
        if (! $request->hasFile($fieldName)) {
            return null;
        }

        $file = $request->file($fieldName);

        if (! $file || ! $file->isValid()) {
            return null;
        }

        $allowedTypes = ['jpg', 'jpeg', 'png', 'webp', 'svg'];
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension());

        if (! in_array($extension, $allowedTypes, true)) {
            abort(422, ucfirst($fieldName).' must be an image file.');
        }

        $mimeType = $file->getMimeType();
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'];

        if (! in_array($mimeType, $allowedMimeTypes, true)) {
            abort(422, ucfirst($fieldName).' has an invalid MIME type.');
        }

        $sizeLimit = $fieldName === 'favicon' ? 1024 * 1024 : 2 * 1024 * 1024;
        if ($file->getSize() > $sizeLimit) {
            abort(422, ucfirst($fieldName).' must be smaller than '.($sizeLimit / 1024 / 1024).'MB.');
        }

        $fileName = $fieldName.'-'.time().'-'.Str::random(8).'.'.$extension;

        return $file->storeAs('settings', $fileName, 'public');
    }

    public function company()
    {
        $tableCheck = $this->ensureSettingsTableExists();
        if ($tableCheck) {
            return $tableCheck;
        }

        $companySettings = Setting::where('group', 'company')
            ->orderBy('key')
            ->get()
            ->keyBy('key');

        $companyFields = [
            'company_name',
            'address',
            'phone',
            'email',
            'website',
            'tax_number',
            'vat_number',
            'logo',
            'favicon',
        ];

        $payload = [];

        foreach ($companyFields as $field) {
            $payload[$field] = $companySettings->get($field)?->value
                ?? ($field === 'company_name' ? optional(tenant())->company_name : null);
        }

        return response()->json($payload);
    }

    public function updateCompany(Request $request)
    {
        $tableCheck = $this->ensureSettingsTableExists();
        if ($tableCheck) {
            return $tableCheck;
        }

        $fields = [
            'company_name' => 'Company name',
            'address' => 'Company address',
            'phone' => 'Company phone',
            'email' => 'Company email',
            'website' => 'Company website',
            'tax_number' => 'Tax number',
            'vat_number' => 'VAT number',
            'logo' => 'Company logo',
            'favicon' => 'Company favicon',
        ];

        $payload = [];

        foreach ($fields as $key => $description) {
            if ($request->exists($key)) {
                $payload[$key] = $this->writeSetting(
                    'company',
                    $key,
                    $request->input($key),
                    $description
                );
            }
        }

        if (empty($payload)) {
            return $this->company();
        }

        return response()->json(array_merge($this->company()->getData(true), $payload));
    }

    public function appearance()
    {
        $tableCheck = $this->ensureSettingsTableExists();
        if ($tableCheck) {
            return $tableCheck;
        }

        $appearanceSettings = Setting::where('group', 'appearance')
            ->orderBy('key')
            ->get()
            ->keyBy('key');

        $appearanceFields = [
            'logo',
            'favicon',
            'theme',
            'primary_color',
            'secondary_color',
        ];

        $payload = [];

        foreach ($appearanceFields as $field) {
            $payload[$field] = $appearanceSettings->get($field)?->value ?? null;
        }

        return response()->json($payload);
    }

    public function invoice()
    {
        $tableCheck = $this->ensureSettingsTableExists();
        if ($tableCheck) {
            return $tableCheck;
        }

        $invoiceDefaults = [
            'invoice_prefix' => 'INV',
            'invoice_start_number' => 1000,
            'invoice_number_format' => '{PREFIX}-{YEAR}-{NUMBER}',
            'invoice_footer' => null,
            'invoice_notes' => null,
            'invoice_currency' => 'NPR',
            'invoice_tax_enabled' => false,
            'invoice_tax_percentage' => 0,
            'invoice_due_days' => 7,
        ];

        $payload = [];

        foreach ($invoiceDefaults as $key => $default) {
            $payload[$key] = $this->getStoredSetting('invoice', $key, $default);
        }

        return response()->json($payload);
    }

    public function updateInvoice(Request $request)
    {
        $tableCheck = $this->ensureSettingsTableExists();
        if ($tableCheck) {
            return $tableCheck;
        }

        if ($request->has('tenant_id') || $request->filled('tenant_id')) {
            abort(422, 'tenant_id is not allowed in invoice settings.');
        }

        $request->validate([
            'invoice_prefix' => ['nullable', 'string', 'max:20'],
            'invoice_start_number' => ['nullable', 'integer', 'min:1'],
            'invoice_number_format' => ['nullable', 'string', 'max:100'],
            'invoice_footer' => ['nullable', 'string', 'max:500'],
            'invoice_notes' => ['nullable', 'string', 'max:500'],
            'invoice_currency' => ['nullable', 'string', 'max:10'],
            'invoice_tax_enabled' => ['nullable', 'boolean'],
            'invoice_tax_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'invoice_due_days' => ['nullable', 'integer', 'min:1'],
        ]);

        $settings = [
            'invoice_prefix' => ['Invoice prefix', 'INV'],
            'invoice_start_number' => ['Invoice start number', 1000],
            'invoice_number_format' => ['Invoice number format', '{PREFIX}-{YEAR}-{NUMBER}'],
            'invoice_footer' => ['Invoice footer', null],
            'invoice_notes' => ['Invoice notes', null],
            'invoice_currency' => ['Invoice currency', 'NPR'],
            'invoice_tax_enabled' => ['Invoice tax enabled', false],
            'invoice_tax_percentage' => ['Invoice tax percentage', 0],
            'invoice_due_days' => ['Invoice due days', 7],
        ];

        $payload = [];

        foreach ($settings as $key => [$description, $default]) {
            if ($request->exists($key)) {
                $value = $request->input($key);

                if ($key === 'invoice_tax_enabled') {
                    $value = (bool) $value;
                }

                $payload[$key] = $this->writeSetting('invoice', $key, $value, $description);
            }
        }

        if (empty($payload)) {
            return $this->invoice();
        }

        return response()->json(array_merge($this->invoice()->getData(true), $payload));
    }

    public function notification()
    {
        $tableCheck = $this->ensureSettingsTableExists();
        if ($tableCheck) {
            return $tableCheck;
        }

        $notificationDefaults = [
            'email_notification_enabled' => true,
            'sms_notification_enabled' => false,
            'invoice_notification_enabled' => true,
            'payment_notification_enabled' => true,
            'booking_notification_enabled' => true,
            'overdue_notification_enabled' => true,
            'fine_notification_enabled' => true,
            'membership_expiry_notification_enabled' => true,
        ];

        $payload = [];

        foreach ($notificationDefaults as $key => $default) {
            $payload[$key] = $this->getStoredSetting('notification', $key, $default);
        }

        return response()->json($payload);
    }

    public function updateNotification(NotificationSettingsRequest $request)
    {
        $tableCheck = $this->ensureSettingsTableExists();
        if ($tableCheck) {
            return $tableCheck;
        }

        $settings = [
            'email_notification_enabled' => 'Enable email notifications.',
            'sms_notification_enabled' => 'Enable SMS notifications.',
            'invoice_notification_enabled' => 'Enable invoice notifications.',
            'payment_notification_enabled' => 'Enable payment notifications.',
            'booking_notification_enabled' => 'Enable booking notifications.',
            'overdue_notification_enabled' => 'Enable overdue notifications.',
            'fine_notification_enabled' => 'Enable fine notifications.',
            'membership_expiry_notification_enabled' => 'Enable membership expiry notifications.',
        ];

        $payload = [];

        foreach ($settings as $key => $description) {
            if ($request->exists($key)) {
                $payload[$key] = $this->writeSetting('notification', $key, (bool) $request->input($key), $description);
            }
        }

        if (empty($payload)) {
            return $this->notification();
        }

        return response()->json(array_merge($this->notification()->getData(true), $payload));
    }

    public function smtp()
    {
        $tableCheck = $this->ensureSettingsTableExists();
        if ($tableCheck) {
            return $tableCheck;
        }

        $smtpDefaults = [
            'smtp_host' => null,
            'smtp_port' => 587,
            'smtp_username' => null,
            'smtp_password' => null,
            'smtp_encryption' => 'tls',
            'smtp_from_address' => null,
            'smtp_from_name' => null,
        ];

        $payload = [];

        foreach ($smtpDefaults as $key => $default) {
            $value = $this->getStoredSetting('smtp', $key, $default);

            if ($key === 'smtp_password' && $value) {
                $value = '********';
            }

            $payload[$key] = $value;
        }

        return response()->json($payload);
    }

    public function updateSmtp(SmtpSettingsRequest $request)
    {
        $tableCheck = $this->ensureSettingsTableExists();
        if ($tableCheck) {
            return $tableCheck;
        }

        $settings = [
            'smtp_host' => 'SMTP host.',
            'smtp_port' => 'SMTP port.',
            'smtp_username' => 'SMTP username.',
            'smtp_password' => 'SMTP password.',
            'smtp_encryption' => 'SMTP encryption.',
            'smtp_from_address' => 'SMTP from address.',
            'smtp_from_name' => 'SMTP from name.',
        ];

        $payload = [];

        foreach ($settings as $key => $description) {
            if ($request->exists($key)) {
                $value = $request->input($key);

                if ($key === 'smtp_port') {
                    $value = (int) $value;
                }

                $payload[$key] = $this->writeSetting('smtp', $key, $value, $description);
            }
        }

        if (empty($payload)) {
            return $this->smtp();
        }

        $currentSettings = $this->smtp()->getData(true);
        foreach ($currentSettings as $key => $value) {
            if ($key === 'smtp_password' && isset($payload[$key])) {
                $payload[$key] = '********';
            }
        }

        return response()->json(array_merge($currentSettings, $payload));
    }

    public function updateAppearance(Request $request)
    {
        $tableCheck = $this->ensureSettingsTableExists();
        if ($tableCheck) {
            return $tableCheck;
        }

        $request->validate([
            'theme' => ['nullable', 'in:light,dark,system'],
            'primary_color' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/'],
            'secondary_color' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/'],
            'logo_path' => ['nullable', 'string', 'max:255'],
            'favicon_path' => ['nullable', 'string', 'max:255'],
        ]);

        $payload = [];

        if ($request->hasFile('logo')) {
            $payload['logo'] = $this->writeSetting(
                'appearance',
                'logo',
                $this->storeImageIfUploaded($request, 'logo'),
                'Tenant logo path.'
            );
        } elseif ($request->filled('logo')) {
            $payload['logo'] = $this->writeSetting(
                'appearance',
                'logo',
                $request->input('logo'),
                'Tenant logo path.'
            );
        }

        if ($request->hasFile('favicon')) {
            $payload['favicon'] = $this->writeSetting(
                'appearance',
                'favicon',
                $this->storeImageIfUploaded($request, 'favicon'),
                'Tenant favicon path.'
            );
        } elseif ($request->filled('favicon')) {
            $payload['favicon'] = $this->writeSetting(
                'appearance',
                'favicon',
                $request->input('favicon'),
                'Tenant favicon path.'
            );
        }

        foreach (['theme', 'primary_color', 'secondary_color'] as $field) {
            if ($request->exists($field)) {
                $payload[$field] = $this->writeSetting(
                    'appearance',
                    $field,
                    $request->input($field),
                    ucfirst(str_replace('_', ' ', $field)).'.'
                );
            }
        }

        if (empty($payload)) {
            return $this->appearance();
        }

        return response()->json(array_merge($this->appearance()->getData(true), $payload));
    }

    public function index(Request $request)
    {
        return SettingResource::collection(
            $this->settingService->getAll($request->query('group'))
        );
    }

    public function store(StoreSettingRequest $request): SettingResource
    {
        $setting = $this->settingService->create(
            $request->validated()
        );

        return new SettingResource($setting);
    }

    public function show(Setting $setting): SettingResource
    {
        return new SettingResource($setting);
    }

    public function update(
        UpdateSettingRequest $request,
        Setting $setting
    ): SettingResource {
        $settingData = $this->settingService->update(
            $setting->id,
            $request->validated()
        );

        return new SettingResource($settingData);
    }

    public function destroy(Setting $setting): JsonResponse
    {
        $this->settingService->delete($setting->id);

        return response()->json([
            'message' => 'Setting deleted successfully.',
        ]);
    }
}
