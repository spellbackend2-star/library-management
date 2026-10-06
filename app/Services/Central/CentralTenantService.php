<?php

namespace App\Services\Central;

use App\Models\CentralInvoice;
use App\Models\Coupon;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\Tenant\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Passport\ClientRepository;
use Spatie\Permission\Models\Role;

class CentralTenantService
{
    public function __construct(
        protected CentralCouponService $centralCouponService,
        protected CentralInvoiceService $centralInvoiceService,
        protected CentralSubscriptionService $centralSubscriptionService
    ) {}

    public function registerTenant(array $data): array
    {
        /*
        |--------------------------------------------------------------------------
        | Get selected subscription plan from CENTRAL database
        |--------------------------------------------------------------------------
        */

        $plan = SubscriptionPlan::query()
            ->where('id', $data['subscription_plan_id'])
            ->where('is_active', true)
            ->first();

        if (! $plan) {
            throw new \RuntimeException(
                'The selected subscription plan is inactive.'
            );
        }

        $couponId = isset($data['coupon_id'])
            ? (int) $data['coupon_id']
            : null;
        $this->centralCouponService->calculateRegistrationCoupon(
            $couponId,
            (float) $plan->price
        );

        /*
        |--------------------------------------------------------------------------
        | Create central tenant
        |--------------------------------------------------------------------------
        */

        $tenant = Tenant::create([
            'company_name' => $data['company_name'],
            'tenant_code' => $data['subdomain'],
            'owner_email' => $data['email'],
            'owner_name' => $data['owner'],
            'status' => 'inactive',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Create tenant domain
        |--------------------------------------------------------------------------
        */

        $domain = $tenant->domains()->create([
            'domain' => $data['subdomain']
                . '.'
                . config('tenancy.central_domains')[0],
        ]);

        try {
            /*
            |--------------------------------------------------------------------------
            | Initialize tenant
            |--------------------------------------------------------------------------
            */

            $tenant->run(function () use (
                $data,
                $tenant

            ) {
                /*
                |--------------------------------------------------------------------------
                | Create tenant owner
                |--------------------------------------------------------------------------
                */

                $owner = User::create([
                    'name' => $data['owner'],
                    'email' => $data['email'],
                    'password' => bcrypt($data['password']),
                ]);

                /*
                |--------------------------------------------------------------------------
                | Seed roles and permissions
                |--------------------------------------------------------------------------
                */

                Artisan::call('db:seed', [
                    '--class' => RolePermissionSeeder::class,
                    '--force' => true,
                ]);

                /*
                |--------------------------------------------------------------------------
                | Assign admin role
                |--------------------------------------------------------------------------
                */

                $adminRole = Role::where('name', 'admin')
                    ->where('guard_name', 'api')
                    ->first();

                if ($adminRole) {
                    $owner->assignRole($adminRole->name);
                }

                /*
                |--------------------------------------------------------------------------
                | Create tenant Passport client
                |--------------------------------------------------------------------------
                */

                $client = app(ClientRepository::class)
                    ->createPasswordGrantClient(
                        name: $data['company_name']
                            . ' Password Grant Client',
                        provider: 'users',
                        confidential: true,
                    );

                /*
                |--------------------------------------------------------------------------
                | Save Passport credentials on central tenant
                |--------------------------------------------------------------------------
                */

                $tenant->update([
                    'passport_client_id' => $client->id,
                    'passport_client_secret' => $client->plainSecret,
                ]);
            });

            /*
            |--------------------------------------------------------------------------
            | Create PENDING subscription in CENTRAL database
            |--------------------------------------------------------------------------
            |
            | IMPORTANT:
            | Subscription belongs to the central tenant.
            | Therefore this must be created outside $tenant->run().
            |
            */

            [$subscription, $invoice, $subscriptionPayment] = DB::transaction(function () use (
                $tenant,
                $plan,
                $couponId
            ): array {
                $couponData = $this->centralCouponService->calculateRegistrationCoupon(
                    $couponId,
                    (float) $plan->price
                );

                $subscription = $this->centralSubscriptionService->createPendingSubscription($tenant, $plan);

                $invoice = $this->centralInvoiceService->createSubscriptionInvoice(
                    $subscription,
                    $tenant->id,
                    (float) $plan->price,
                    $couponData['coupon_id'],
                    $couponData['coupon_discount']
                );

                $subscriptionPayment = SubscriptionPayment::create([
                    'subscription_id' => $subscription->id,
                    'invoice_id' => $invoice->id,
                    'tenant_id' => $tenant->id,
                    'amount' => (float) $invoice->total_amount,
                    'payment_method' => 'CASH',
                    'status' => 'PENDING',
                    'transaction_id' => null,
                    'gateway_response' => null,
                    'paid_at' => null,
                ]);

                $invoice->update([
                    'subscription_payment_id' => $subscriptionPayment->id,
                ]);

                $this->centralCouponService->recordUsage($couponData['coupon_id']);

                return [$subscription, $invoice, $subscriptionPayment];
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new \RuntimeException(
                'Failed to create tenant: ' . $e->getMessage()
            );
        }

        return [
            'tenant' => $tenant->fresh(),
            'domain' => $domain->domain,
            'subscription' => $subscription->load('plan'),
            'subscription_payment' => $subscriptionPayment->fresh()->load(['subscription.plan', 'tenant', 'invoice']),
            'invoice' => $invoice,
        ];
    }

    public function completePaymentAndCreateTenant(
        SubscriptionPayment $payment,
        array $data
    ): array {
        $payment->load(['subscription.plan', 'invoice']);

        $hasSuccessfulPayment = in_array(
            $payment->status,
            ['SUCCESS', 'COMPLETED'],
            true
        );
        $hasPartialInvoicePayment = (float) $payment->invoice?->paid_amount > 0;

        if (! $hasSuccessfulPayment && ! $hasPartialInvoicePayment) {
            throw new \RuntimeException(
                'A successful payment or partial invoice payment is required before creating a tenant.'
            );
        }

        $subscription = $payment->subscription;

        if (! $subscription || ! $subscription->plan) {
            throw new \RuntimeException('Subscription plan not found for this payment.');
        }

        if ($payment->tenant_id || $subscription->tenant_id) {
            throw new \RuntimeException('This payment is already linked to a tenant.');
        }

        $centralDomain = config('tenancy.central_domains.0');

        if (! $centralDomain) {
            throw new \RuntimeException('The central domain is not configured.');
        }

        $tenant = Tenant::create([
            'company_name' => $data['company_name'],
            'tenant_code' => $data['subdomain'],
            'owner_email' => $data['email'],
            'owner_name' => $data['owner'],
            'status' => 'pending',
        ]);

        $domain = $tenant->domains()->create([
            'domain' => $data['subdomain'].'.'.$centralDomain,
        ]);

        $subscription->update(['tenant_id' => $tenant->id]);
        $payment->update(['tenant_id' => $tenant->id]);
        $payment->invoice?->update(['tenant_id' => $tenant->id]);

        try {
            $tenant->run(function () use ($data, $tenant): void {
                $owner = User::create([
                    'name' => $data['owner'],
                    'email' => $data['email'],
                    'password' => Hash::make($data['password']),
                ]);

                Artisan::call('db:seed', [
                    '--class' => RolePermissionSeeder::class,
                    '--force' => true,
                ]);

                $adminRole = Role::where('name', 'admin')
                    ->where('guard_name', 'api')
                    ->first();

                if ($adminRole) {
                    $owner->assignRole($adminRole->name);
                }

                $client = app(ClientRepository::class)
                    ->createPasswordGrantClient(
                        name: $data['company_name'].' Password Grant Client',
                        provider: 'users',
                        confidential: true,
                    );

                $tenant->update([
                    'passport_client_id' => $client->id,
                    'passport_client_secret' => $client->plainSecret,
                ]);
            });
        } catch (\Throwable $e) {
            throw new \RuntimeException(
                'Failed to provision tenant: '.$e->getMessage(),
                previous: $e
            );
        }

        if ($subscription->status !== 'active') {
            $this->centralSubscriptionService->activateSubscription($subscription, $subscription->plan);
        }

        $payment = $payment->fresh()->load([
            'subscription.plan',
            'tenant',
            'invoice',
        ]);

        return [
            'tenant' => $tenant->fresh(),
            'domain' => $domain->domain,
            'subscription' => $subscription->fresh()->load('plan'),
            'subscription_payment' => $payment->fresh()->load([
                'subscription.plan',
                'tenant',
                'invoice',
            ]),
            'invoice' => $payment->invoice()->first(),
        ];
    }
}
