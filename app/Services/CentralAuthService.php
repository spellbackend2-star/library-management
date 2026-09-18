<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use App\Repositories\Interface\TenantInterface;
use Database\Seeders\Tenant\RolePermissionSeeder;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
            !$centralUser ||
            !Hash::check($password, $centralUser->password)
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

        if (!$clientId || !$clientSecret) {
            throw new \RuntimeException(
                'Central Passport client credentials are not configured.'
            );
        }

        $client = DB::table('oauth_clients')
            ->where('id', $clientId)
            ->where('revoked', false)
            ->first();

        if (!$client) {
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
                new Response()
            );

        $token = json_decode(
            (string) $passportResponse->getContent(),
            true
        );

        if (!is_array($token) || isset($token['error'])) {
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
     * 8. Payment happens separately.
     * 9. Tenant is activated only after successful payment.
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

        if (!$plan) {
            throw new \RuntimeException(
                'Selected subscription plan is not available.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Create central tenant
        |--------------------------------------------------------------------------
        */

        $tenant = Tenant::create([
            'company_name' => $data['company_name'],
            'tenant_code' => $data['subdomain'],
            'owner_email' => $data['email'],
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
                $tenant,
                $plan
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

            $subscription = Subscription::create([
                'tenant_id' => $tenant->id,
                'subscription_plan_id' => $plan->id,
                'amount' => $plan->price,
                'starts_at' => null,
                'expires_at' => null,
                'status' => 'pending',
            ]);

            SubscriptionPayment::create([
                'subscription_id' => $subscription->id,
                'tenant_id' => $tenant->id,
                'amount' => $subscription->amount,
                'payment_method' => 'CASH',
                'status' => 'PENDING',
                'transaction_id' => null,
                'gateway_response' => null,
                'paid_at' => null,
            ]);
        } catch (\Throwable $e) {
            throw new \RuntimeException(
                'Failed to create tenant: ' . $e->getMessage()
            );
        }

        return [
            'tenant' => $tenant->fresh(),
            'domain' => $domain->domain,
            'subscription' => $subscription->load('plan'),
        ];
    }

    public function completeCentralCashPayment(SubscriptionPayment $payment): SubscriptionPayment
    {
        return DB::transaction(function () use ($payment) {
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

            $startDate = now();
            $expiresAt = match (strtolower($plan->duration_unit ?? 'month')) {
                'day' => $startDate->copy()->addDays((int) $plan->duration),
                'month' => $startDate->copy()->addMonths((int) $plan->duration),
                'year' => $startDate->copy()->addYears((int) $plan->duration),
                default => $startDate->copy()->addMonths((int) $plan->duration),
            };

            $subscription->update([
                'status' => 'active',
                'starts_at' => $startDate->toDateString(),
                'expires_at' => $expiresAt->toDateString(),
            ]);

            $tenant = $payment->tenant()->first() ?? $subscription->tenant()->first();

            if ($tenant) {
                $tenant->update([
                    'status' => 'active',
                ]);
            }

            return $payment->fresh()->load(['subscription.plan', 'tenant']);
        });
    }

    public function activateSubscriptionPayment(SubscriptionPayment $payment): SubscriptionPayment
    {
        return $this->completeCentralCashPayment($payment);
    }
}