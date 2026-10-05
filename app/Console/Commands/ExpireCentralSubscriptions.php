<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireCentralSubscriptions extends Command
{
    protected $signature = 'central:expire-subscriptions';

    protected $description = 'Expire central subscriptions and suspend tenants whose package has expired';

    public function handle(): int
    {
        $expiredCount = 0;
        $suspendedCount = 0;

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

        $this->info(sprintf(
            'Central subscription expiry complete. Expired=%d tenants_suspended=%d',
            $expiredCount,
            $suspendedCount
        ));

        return self::SUCCESS;
    }
}
