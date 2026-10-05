<?php

namespace App\Services;

use App\Models\CentralInvoice;
use App\Models\Coupon;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Repositories\Interface\TenantInterface;
use Database\Seeders\Tenant\RolePermissionSeeder;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Http\Controllers\AccessTokenController;
use Psr\Http\Message\ServerRequestInterface;
use Spatie\Permission\Models\Role;

class CentralAuthService
{
    public function __construct(
        protected TenantInterface $tenantRepository
    ) {}

    /**
     * Authenticate central admin only.
     */
    public function login(
        string $email,
        string $password,
        ServerRequestInterface $serverRequest
    ): array {
        $centralUser = User::where('email', $email)->first();

        if (
            ! $centralUser ||
            ! Hash::check($password, $centralUser->password)
        ) {
            throw new \RuntimeException('Invalid credentials.');
        }

        $token = $this->issueCentralToken(
            $email,
            $password,
            $serverRequest
        );

        return [
            'token' => $token,
            'tenant' => null,
            'user' => $centralUser,
        ];
    }

    /**
     * Issue Passport token for central admin.
     */
    protected function issueCentralToken(
        string $email,
        string $password,
        ServerRequestInterface $serverRequest
    ): array {
        $clientId = config('passport.central_client_id');
        $clientSecret = config('passport.central_client_secret');

        if (! $clientId || ! $clientSecret) {
            throw new \RuntimeException(
                'Central Passport client credentials are not configured.'
            );
        }

        $client = DB::table('oauth_clients')
            ->where('id', $clientId)
            ->where('revoked', false)
            ->first();

        if (! $client) {
            throw new \RuntimeException(
                'Central Passport client not found.'
            );
        }

        $tokenRequest = $serverRequest->withParsedBody([
            'grant_type' => 'password',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'username' => $email,
            'password' => $password,
            'scope' => '*',
        ]);

        $passportResponse = app(AccessTokenController::class)
            ->issueToken(
                $tokenRequest,
                new Response
            );

        $token = json_decode(
            (string) $passportResponse->getContent(),
            true
        );

        if (! is_array($token) || isset($token['error'])) {
            throw new \RuntimeException(
                $token['error_description']
                    ?? $token['message']
                    ?? 'Unable to issue central access token.'
            );
        }

        return $token;
    }

    /**
     * Get tenant by authenticated user's email.
     */
    public function getTenantForUser(User $user): ?Tenant
    {
        return $this->tenantRepository->findByOwnerEmail(
            $user->email
        );
    }

    /**
     * Register a new tenant with a pending subscription.
     *
     * Flow:
     * 1. Select subscription plan.
     * 2. Create tenant.
     * 3. Create tenant domain.
     * 4. Provision tenant database.
     * 5. Create tenant owner.
     * 6. Create pending subscription.
     * 7. Create tenant Passport client.
     * 8. Create subscription invoice (before payment).
     * 9. Create pending payment linked to invoice.
     * 10. Payment happens separately.
     * 11. Tenant is activated only after successful payment.
     */
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
        $this->calculateRegistrationCoupon(
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
                $couponData = $this->calculateRegistrationCoupon(
                    $couponId,
                    (float) $plan->price
                );

                $subscription = Subscription::create([
                    'tenant_id' => $tenant->id,
                    'subscription_plan_id' => $plan->id,
                    'amount' => (float) $plan->price,
                    'starts_at' => null,
                    'expires_at' => null,
                    'status' => 'pending',
                ]);

                $invoice = $this->createSubscriptionInvoice(
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

                if ($couponData['coupon_id']) {
                    Coupon::whereKey($couponData['coupon_id'])
                        ->increment('used_count');
                }

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

    /**
     * Create a subscription invoice for a given subscription.
     *
     * The invoice amount is derived from the subscription amount.
     * This method does NOT accept amount from external callers.
     */
    protected function createSubscriptionInvoice(
        Subscription $subscription,
        string $tenantId,
        float $amount,
        ?int $couponId = null,
        float $couponDiscount = 0
    ): CentralInvoice {
        $totalAmount = round($amount - $couponDiscount, 2);

        return CentralInvoice::create([
            'tenant_id' => $tenantId,
            'subscription_id' => $subscription->id,
            'invoice_number' => CentralInvoice::generateInvoiceNumber(),
            'invoice_type' => 'subscription',
            'subtotal' => $amount,
            'tax' => 0,
            'discount' => 0,
            'coupon_id' => $couponId,
            'coupon_discount' => $couponDiscount,
            'total_amount' => $totalAmount,
            'paid_amount' => 0,
            'remaining_amount' => $totalAmount,
            'currency' => 'NPR',
            'currency_symbol' => 'Rs.',
            'status' => 'unpaid',
            'due_date' => now()->addDays(7)->toDateString(),
            'notes' => null,
        ]);
    }

    /**
     * Validate a registration coupon and return its applied discount.
     *
     * Call inside a transaction when reserving the coupon usage.
     */
    protected function calculateRegistrationCoupon(?int $couponId, float $amount): array
    {
        if (! $couponId) {
            return [
                'coupon_id' => null,
                'coupon_discount' => 0.0,
            ];
        }

        $coupon = Coupon::query()
            ->lockForUpdate()
            ->find($couponId);

        if (! $coupon) {
            throw ValidationException::withMessages([
                'coupon_id' => ['The selected coupon is invalid.'],
            ]);
        }

        if (! $coupon->is_active) {
            throw ValidationException::withMessages([
                'coupon_id' => ['This coupon is inactive.'],
            ]);
        }

        if ($coupon->valid_from && now()->lt($coupon->valid_from)) {
            throw ValidationException::withMessages([
                'coupon_id' => ['This coupon is not yet valid.'],
            ]);
        }

        if ($coupon->valid_until && now()->gt($coupon->valid_until)) {
            throw ValidationException::withMessages([
                'coupon_id' => ['This coupon has expired.'],
            ]);
        }

        if ($coupon->max_uses !== null && $coupon->used_count >= $coupon->max_uses) {
            throw ValidationException::withMessages([
                'coupon_id' => ['Coupon usage limit reached.'],
            ]);
        }

        if ($amount < (float) $coupon->min_order_value) {
            throw ValidationException::withMessages([
                'coupon_id' => ['Minimum order value not met for this coupon.'],
            ]);
        }

        $discount = match (strtoupper((string) $coupon->discount_type)) {
            'PERCENT' => round($amount * ((float) $coupon->discount_value / 100), 2),
            'FLAT' => round((float) $coupon->discount_value, 2),
            default => 0.0,
        };

        if ($coupon->max_discount !== null) {
            $discount = min($discount, (float) $coupon->max_discount);
        }

        return [
            'coupon_id' => $coupon->id,
            'coupon_discount' => min($discount, $amount),
        ];
    }

    public function completePaymentAndCreateTenant(
        SubscriptionPayment $payment,
        array $data
    ): array {
        $payment->loadMissing(['subscription.plan', 'invoice']);

        if (! in_array($payment->status, ['PENDING', 'SUCCESS', 'COMPLETED'], true)) {
            throw new \RuntimeException(
                'Payment must be pending or successfully completed before creating a tenant.'
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
            'status' => 'inactive',
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

        if ($payment->status === 'PENDING') {
            $payment = $this->completeCentralCashPayment($payment);
        } else {
            $tenant->update(['status' => 'active']);

            if ($subscription->status !== 'active') {
                $startDate = now();
                $expiresAt = match (strtolower($subscription->plan->duration_unit ?? 'month')) {
                    'day' => $startDate->copy()->addDays((int) $subscription->plan->duration),
                    'month' => $startDate->copy()->addMonths((int) $subscription->plan->duration),
                    'year' => $startDate->copy()->addYears((int) $subscription->plan->duration),
                    default => $startDate->copy()->addMonths((int) $subscription->plan->duration),
                };

                $subscription->update([
                    'status' => 'active',
                    'starts_at' => $startDate->toDateString(),
                    'expires_at' => $expiresAt->toDateString(),
                ]);
            }

            $payment->invoice?->update([
                'status' => 'paid',
                'paid_amount' => $payment->invoice->total_amount,
                'remaining_amount' => 0,
            ]);

            $payment = $payment->fresh()->load([
                'subscription.plan',
                'tenant',
                'invoice',
            ]);
        }

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

    public function completeCentralCashPayment(SubscriptionPayment $payment): SubscriptionPayment
    {
        return DB::transaction(function () use ($payment) {
            $payment = SubscriptionPayment::query()
                ->lockForUpdate()
                ->findOrFail($payment->id);

            if (in_array($payment->status, ['SUCCESS', 'COMPLETED'], true)) {
                return $payment->fresh()->load([
                    'subscription.plan',
                    'tenant',
                    'invoice',
                ]);
            }

            $invoice = CentralInvoice::query()
                ->lockForUpdate()
                ->find($payment->invoice_id);

            if (! $invoice) {
                throw new \RuntimeException('Invoice not found for this payment.');
            }

            $totalAmount = round((float) $invoice->total_amount, 2);
            $currentPaid = round((float) $invoice->paid_amount, 2);
            $paymentAmount = round((float) $payment->amount, 2);
            $remainingAmount = max(0, round($totalAmount - $currentPaid, 2));

            if ($paymentAmount > $remainingAmount) {
                throw ValidationException::withMessages([
                    'amount' => [
                        "The payment amount cannot exceed the remaining balance of {$remainingAmount}.",
                    ],
                ]);
            }

            $newPaid = round($currentPaid + $paymentAmount, 2);
            $newRemaining = max(0, round($totalAmount - $newPaid, 2));

            if ($newPaid > $totalAmount) {
                throw ValidationException::withMessages([
                    'amount' => ['The payment amount cannot exceed the invoice total.'],
                ]);
            }

            $subscription = $payment->subscription()->first();

            if (! $subscription) {
                throw new \RuntimeException('Subscription not found for this payment.');
            }

            $plan = $subscription->plan()->first();

            if (! $plan) {
                throw new \RuntimeException('Subscription plan not found for this payment.');
            }

            $payment->update([
                'status' => 'SUCCESS',
                'paid_at' => now(),
            ]);

            if ($subscription->status !== 'active') {
                $startDate = now();
                $expiresAt = match (strtolower($plan->duration_unit ?? 'month')) {
                    'day' => $startDate->copy()->addDays((int) $plan->duration),
                    'month' => $startDate->copy()->addMonths((int) $plan->duration),
                    'year' => $startDate->copy()->addYears((int) $plan->duration),
                    default => $startDate->copy()->addMonths((int) $plan->duration),
                };

                $subscription->update([
                    'starts_at' => $startDate->toDateString(),
                    'expires_at' => $expiresAt->toDateString(),
                ]);
            }

            $subscription->update([
                'status' => 'active',
            ]);

            $tenant = $payment->tenant()->first() ?? $subscription->tenant()->first();

            if ($tenant) {
                $tenant->update([
                    'status' => 'active',
                ]);

                $payment->update(['tenant_id' => $tenant->id]);
                $subscription->update(['tenant_id' => $tenant->id]);
                $invoice->update(['tenant_id' => $tenant->id]);
            }

            $invoice->update([
                'paid_amount' => $newPaid,
                'remaining_amount' => $newRemaining,
                'status' => $newRemaining <= 0 ? 'paid' : 'partially_paid',
            ]);

            return $payment->fresh()->load(['subscription.plan', 'tenant', 'invoice']);
        });
    }

    public function completePayment(Payment $payment): Payment
    {
        return DB::transaction(function () use ($payment) {

            /*
         * Prevent duplicate completion.
         */
            if ($payment->status !== 'SUCCESS') {
                $payment->update([
                    'status' => 'SUCCESS',
                    'paid_at' => now(),
                ]);
            }

            /*
         * Invoice payment
         */
            if ($payment->invoice_id) {

                $invoice = Invoice::query()
                    ->lockForUpdate()
                    ->findOrFail($payment->invoice_id);

                /*
             * Only SUCCESS payments count toward invoice payment.
             */
                $paidAmount = $invoice->payments()
                    ->where('status', 'SUCCESS')
                    ->sum('amount');

                $paidAmount = round((float) $paidAmount, 2);

                $remainingAmount = max(
                    0,
                    round(
                        (float) $invoice->total_amount - $paidAmount,
                        2
                    )
                );

                /*
             * Partial payment is allowed.
             */
                $status = $remainingAmount <= 0
                    ? 'paid'
                    : ($paidAmount > 0 ? 'partially_paid' : 'unpaid');

                $invoice->update([
                    'paid_amount' => $paidAmount,
                    'remaining_amount' => $remainingAmount,
                    'status' => $status,
                ]);

                /*
             * Activate package ONLY after full payment.
             */
                if ($status === 'paid') {

                    app(InvoiceService::class)
                        ->activateMemberPackage($invoice);

                    app(FineService::class)
                        ->syncFineStatusOnInvoicePaid($invoice);
                }

                return $payment->fresh([
                    'invoice',
                ]);
            }

            /*
         * Booking payment
         */
            if ($payment->booking_id) {

                $booking = $payment->booking;

                $booking->update([
                    'status' => 'CONFIRMED',
                    'payment_status' => 'PAID',
                    'confirmed_at' => now(),
                ]);

                return $payment->fresh();
            }

            throw new \Exception(
                'Payment must belong to either an invoice or booking.'
            );
        });
    }

    public function activateSubscriptionPayment(SubscriptionPayment $payment): SubscriptionPayment
    {
        return $this->completeCentralCashPayment($payment);
    }
}
