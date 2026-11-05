<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\CentralSetting;
use App\Services\CentralSettingService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ExpireCentralSubscriptions extends Command
{
    protected $signature = 'central:expire-subscriptions';

    protected $description = 'Expire central subscriptions and suspend tenants whose package has expired';

    public function handle(): int
    {
        $expiredCount = 0;
        $suspendedCount = 0;
        $notificationCount = 0;

        Subscription::query()
            ->where('status', 'active')
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '<', today())
            ->select('id')
            ->orderBy('id')
            ->chunkById(100, function ($subscriptions) use (&$expiredCount, &$suspendedCount): void {
                foreach ($subscriptions as $subscriptionRow) {
                    DB::transaction(function () use (
                        $subscriptionRow,
                        &$expiredCount,
                        &$suspendedCount
                    ): void {
                        $subscription = Subscription::query()
                            ->lockForUpdate()
                            ->find($subscriptionRow->id);

                        if (
                            ! $subscription
                            || $subscription->status !== 'active'
                            || ! $subscription->expires_at
                            || $subscription->expires_at >= today()->toDateString()
                        ) {
                            return;
                        }

                        $subscription->update(['status' => 'expired']);
                        $expiredCount++;

                        CentralSetting::query()->updateOrCreate(
                            [
                                'group' => 'notification_delivery',
                                'key' => 'subscription_expiry_'.$subscription->id,
                            ],
                            [
                                'value' => 'pending',
                                'type' => 'string',
                                'description' => 'Central subscription expiry notification status.',
                                'is_locked' => true,
                            ]
                        );

                        if (! $subscription->tenant_id) {
                            return;
                        }

                        $hasValidActiveSubscription = Subscription::query()
                            ->where('tenant_id', $subscription->tenant_id)
                            ->where('status', 'active')
                            ->where(function ($query): void {
                                $query->whereNull('expires_at')
                                    ->orWhereDate('expires_at', '>=', today());
                            })
                            ->exists();

                        if ($hasValidActiveSubscription) {
                            return;
                        }

                        $tenant = $subscription->tenant;

                        if ($tenant && $tenant->status === 'active') {
                            $tenant->update([
                                'status' => 'suspended',
                                'suspension_reason' => 'EXPIRED',
                            ]);
                            $suspendedCount++;
                        }
                    });
                }
            });

        $notificationCount = $this->sendPendingExpiryNotifications();

        $this->info(sprintf(
            'Central subscription expiry complete. Expired=%d tenants_suspended=%d notifications_sent=%d',
            $expiredCount,
            $suspendedCount,
            $notificationCount,
        ));

        return self::SUCCESS;
    }

    private function sendPendingExpiryNotifications(): int
    {
        $settings = app(CentralSettingService::class);

        if (
            ! $this->enabled($settings->get('email_notification_enabled', true))
            || ! $this->enabled($settings->get('subscription_notification_enabled', true))
        ) {
            return 0;
        }

        $sent = 0;

        CentralSetting::query()
            ->where('group', 'notification_delivery')
            ->where('key', 'like', 'subscription_expiry_%')
            ->where('value', 'pending')
            ->orderBy('id')
            ->chunkById(100, function ($markers) use ($settings, &$sent): void {
                foreach ($markers as $marker) {
                    $subscriptionId = (int) str_replace('subscription_expiry_', '', $marker->key);

                    try {
                        $subscription = Subscription::query()
                            ->with(['tenant', 'plan'])
                            ->find($subscriptionId);
                        $tenant = $subscription?->tenant;

                        if (! $subscription) {
                            $marker->update(['value' => 'skipped']);
                            continue;
                        }

                        if (! $tenant?->owner_email) {
                            $marker->update(['value' => 'skipped']);
                            continue;
                        }

                        $smtp = $settings->getGroup(CentralSettingService::SMTP_GROUP);
                        $fromAddress = $smtp['smtp_from_address'] ?? config('mail.from.address');
                        $fromName = $smtp['smtp_from_name']
                            ?? $settings->get('platform_name', config('app.name'));

                        if (! empty($smtp['smtp_host'])) {
                            Config::set('mail.mailers.central-expiry', [
                                'transport' => 'smtp',
                                'scheme' => ($smtp['smtp_encryption'] ?? null) === 'ssl' ? 'smtps' : 'smtp',
                                'url' => null,
                                'host' => $smtp['smtp_host'],
                                'port' => (int) ($smtp['smtp_port'] ?? 587),
                                'username' => $smtp['smtp_username'] ?? null,
                                'password' => $smtp['smtp_password'] ?? null,
                                'timeout' => null,
                                'local_domain' => parse_url((string) config('app.url'), PHP_URL_HOST),
                            ]);
                            Mail::purge('central-expiry');
                            $mailer = Mail::mailer('central-expiry');
                        } else {
                            $mailer = Mail::mailer();
                        }

                        $planName = $subscription->plan?->name ?? 'your subscription plan';
                        $expiresAt = $subscription->expires_at
                            ? Carbon::parse($subscription->expires_at)->format('F j, Y')
                            : 'an unspecified date';
                        $mailer->raw(
                            "Hello {$tenant->owner_name},\n\n"
                                ."Your {$planName} subscription for {$tenant->company_name} expired on {$expiresAt}.\n"
                                .'Please sign in to renew your subscription and restore full service access.',
                            function ($message) use ($tenant, $fromAddress, $fromName): void {
                                $message
                                    ->to($tenant->owner_email, $tenant->owner_name)
                                    ->subject('Your library platform subscription has expired');

                                if ($fromAddress) {
                                    $message->from($fromAddress, $fromName);
                                }
                            }
                        );

                        $marker->update(['value' => 'sent']);
                        $sent++;
                    } catch (\Throwable $e) {
                        Log::warning('Central subscription expiry notification failed', [
                            'marker' => $marker->key,
                            'error' => $e->getMessage(),
                        ]);
                    } finally {
                        Mail::purge('central-expiry');
                    }
                }
            });

        return $sent;
    }

    private function enabled(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
