<?php

namespace App\Console\Commands;

use App\Models\Fine;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\Setting;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ProcessTenantExpirations extends Command
{
    protected $signature = 'tenants:process-expirations';

    protected $description = 'Expire tenant memberships and invoices and email members about expiry, invoices, and fines';

    public function handle(): int
    {
        $summary = [
            'tenants' => 0,
            'members_expired' => 0,
            'invoices_overdue' => 0,
            'emails_sent' => 0,
            'failed' => 0,
        ];

        foreach (Tenant::on('mysql')->get() as $tenant) {
            $databaseName = $tenant->database()->getName();
            $databaseManager = $tenant->database()->manager();

            if (! $databaseManager->databaseExists($databaseName)) {
                $summary['failed']++;
                $this->warn("Skipping tenant {$tenant->id} ({$tenant->company_name}): database {$databaseName} not found.");
                Log::warning('Tenant expiry processing skipped missing tenant database', [
                    'tenant' => $tenant->id,
                    'database' => $databaseName,
                ]);

                continue;
            }

            try {
                tenancy()->initialize($tenant);
                $summary['tenants']++;

                $summary['members_expired'] += Member::query()
                    ->where('status', 'active')
                    ->whereNotNull('membership_expiry')
                    ->whereDate('membership_expiry', '<', Carbon::today())
                    ->update(['status' => 'expired']);

                $summary['invoices_overdue'] += Invoice::query()
                    ->whereIn('status', ['unpaid', 'partially_paid'])
                    ->whereNotNull('due_date')
                    ->whereDate('due_date', '<', Carbon::today())
                    ->update(['status' => 'overdue']);

                $settings = $this->notificationSettings();

                if ($settings['email_notification_enabled']) {
                    $summary['emails_sent'] += $this->notifyExpiringMembers(
                        $tenant,
                        $settings['membership_expiry_notification_enabled'],
                        $summary['failed']
                    );

                    $summary['emails_sent'] += $this->notifyOverdueInvoices(
                        $tenant,
                        $settings['invoice_notification_enabled'],
                        $summary['failed']
                    );

                    $summary['emails_sent'] += $this->notifyUnpaidFines(
                        $tenant,
                        $settings['fine_notification_enabled'],
                        $summary['failed']
                    );
                }
            } catch (\Throwable $e) {
                $summary['failed']++;
                $this->warn("Skipping tenant {$tenant->id} ({$tenant->company_name}): {$e->getMessage()}");
                Log::warning('Tenant expiry processing failed', [
                    'tenant' => $tenant->id,
                    'error' => $e->getMessage(),
                ]);
            } finally {
                Mail::purge('tenant-expiry');

                if (tenancy()->initialized) {
                    tenancy()->end();
                }
            }
        }

        $this->info(sprintf(
            'Tenant expiry processing complete. Tenants=%d members_expired=%d invoices_overdue=%d emails_sent=%d failed=%d',
            $summary['tenants'],
            $summary['members_expired'],
            $summary['invoices_overdue'],
            $summary['emails_sent'],
            $summary['failed'],
        ));

        return self::SUCCESS;
    }

    private function notificationSettings(): array
    {
        $settings = Setting::query()
            ->where('group', 'notification')
            ->get()
            ->keyBy('key');

        return [
            'email_notification_enabled' => $this->settingIsEnabled(
                $settings->get('email_notification_enabled')?->value,
                true
            ),
            'membership_expiry_notification_enabled' => $this->settingIsEnabled(
                $settings->get('membership_expiry_notification_enabled')?->value,
                true
            ),
            'invoice_notification_enabled' => $this->settingIsEnabled(
                $settings->get('invoice_notification_enabled')?->value,
                true
            ),
            'fine_notification_enabled' => $this->settingIsEnabled(
                $settings->get('fine_notification_enabled')?->value,
                true
            ),
        ];
    }

    private function settingIsEnabled(mixed $value, bool $default): bool
    {
        return $value === null
            ? $default
            : filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private function notifyExpiringMembers(Tenant $tenant, bool $enabled, int &$failed): int
    {
        if (! $enabled) {
            return 0;
        }

        $sent = 0;

        Member::query()
            ->where('status', 'active')
            ->whereDate('membership_expiry', Carbon::today())
            ->with('package')
            ->orderBy('id')
            ->chunkById(100, function ($members) use ($tenant, &$sent, &$failed): void {
                foreach ($members as $member) {
                    $key = 'membership_expiry_member_'.$member->id;

                    if ($this->alreadyNotified($key, $member->membership_expiry?->toDateString())) {
                        continue;
                    }

                    $body = "Hello {$member->first_name},\n\n"
                        .'Your library membership expires today ('
                        .$member->membership_expiry?->format('F j, Y').").\n";

                    if ($member->package?->name) {
                        $body .= 'Membership package: '.$member->package->name."\n";
                    }

                    $body .= "Please contact {$tenant->company_name} to renew your membership.\n\nThank you.";

                    if ($this->sendMemberEmail($tenant, $member, 'Your library membership expires today', $body, $key, $member->membership_expiry?->toDateString())) {
                        $sent++;
                    } else {
                        $failed++;
                    }
                }
            });

        return $sent;
    }

    private function notifyOverdueInvoices(Tenant $tenant, bool $enabled, int &$failed): int
    {
        if (! $enabled) {
            return 0;
        }

        $sent = 0;

        Invoice::query()
            ->where('status', 'overdue')
            ->where('invoice_type', '!=', 'fine')
            ->with('member')
            ->orderBy('id')
            ->chunkById(100, function ($invoices) use ($tenant, &$sent, &$failed): void {
                foreach ($invoices as $invoice) {
                    $member = $invoice->member;
                    if (! $member) {
                        continue;
                    }

                    $key = 'invoice_overdue_'.$invoice->id;
                    if ($this->alreadyNotified($key, (string) $invoice->id)) {
                        continue;
                    }

                    $body = "Hello {$member->first_name},\n\n"
                        ."Invoice {$invoice->invoice_number} is overdue.\n"
                        .'Amount remaining: '.number_format((float) $invoice->remaining_amount, 2)."\n"
                        .'Due date: '.$invoice->due_date?->format('F j, Y')."\n\n"
                        .'Please contact '.$tenant->company_name.' to arrange payment.';

                    if ($this->sendMemberEmail($tenant, $member, 'Overdue library invoice '.$invoice->invoice_number, $body, $key, (string) $invoice->id)) {
                        $sent++;
                    } else {
                        $failed++;
                    }
                }
            });

        return $sent;
    }

    private function notifyUnpaidFines(Tenant $tenant, bool $enabled, int &$failed): int
    {
        if (! $enabled) {
            return 0;
        }

        $sent = 0;

        Fine::query()
            ->where('status', 'unpaid')
            ->whereDate('issued_date', '<=', Carbon::today())
            ->with('member')
            ->orderBy('id')
            ->chunkById(100, function ($fines) use ($tenant, &$sent, &$failed): void {
                foreach ($fines as $fine) {
                    $member = $fine->member;
                    if (! $member) {
                        continue;
                    }

                    $key = 'fine_'.$fine->id;
                    if ($this->alreadyNotified($key, (string) $fine->id)) {
                        continue;
                    }

                    $body = "Hello {$member->first_name},\n\n"
                        .'A fine has been issued for your account. Reason: '.$fine->reason."\n"
                        .'Amount: '.number_format((float) $fine->amount, 2)."\n"
                        .'Days late: '.(int) $fine->days_late."\n\n"
                        .'Please contact '.$tenant->company_name.' if you have questions.';

                    if ($this->sendMemberEmail($tenant, $member, 'Library fine issued', $body, $key, (string) $fine->id)) {
                        $sent++;
                    } else {
                        $failed++;
                    }
                }
            });

        return $sent;
    }

    private function alreadyNotified(string $key, ?string $value): bool
    {
        return Setting::query()
            ->where('group', 'notification_delivery')
            ->where('key', $key)
            ->value('value') === $value;
    }

    private function sendMemberEmail(
        Tenant $tenant,
        Member $member,
        string $subject,
        string $body,
        string $deliveryKey,
        ?string $deliveryValue
    ): bool {
        try {
            $settings = Setting::query()
                ->where('group', 'smtp')
                ->get()
                ->keyBy('key');

            $smtpHost = $settings->get('smtp_host')?->value;
            $fromAddress = $settings->get('smtp_from_address')?->value
                ?: config('mail.from.address');
            $fromName = $settings->get('smtp_from_name')?->value
                ?: $tenant->company_name
                ?: config('mail.from.name');

            if ($smtpHost) {
                $encryption = $settings->get('smtp_encryption')?->value;
                Config::set('mail.mailers.tenant-expiry', [
                    'transport' => 'smtp',
                    'scheme' => $encryption === 'ssl' ? 'smtps' : 'smtp',
                    'url' => null,
                    'host' => $smtpHost,
                    'port' => (int) ($settings->get('smtp_port')?->value ?: 587),
                    'username' => $settings->get('smtp_username')?->value,
                    'password' => $settings->get('smtp_password')?->value,
                    'timeout' => null,
                    'local_domain' => parse_url((string) config('app.url'), PHP_URL_HOST),
                ]);
                Mail::purge('tenant-expiry');
                $mailer = Mail::mailer('tenant-expiry');
            } else {
                $mailer = Mail::mailer();
            }

            $mailer->raw($body, function ($message) use ($member, $subject, $fromAddress, $fromName): void {
                $message
                    ->to($member->email, trim($member->first_name.' '.$member->last_name))
                    ->subject($subject);

                if ($fromAddress) {
                    $message->from($fromAddress, $fromName);
                }
            });

            Setting::query()->updateOrCreate(
                [
                    'group' => 'notification_delivery',
                    'key' => $deliveryKey,
                ],
                [
                    'value' => $deliveryValue,
                    'type' => 'string',
                    'description' => 'Expiry or billing notification delivery marker.',
                    'is_locked' => true,
                ]
            );

            return true;
        } catch (\Throwable $e) {
            Log::warning('Tenant member notification email failed', [
                'tenant' => $tenant->id,
                'member' => $member->id,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
