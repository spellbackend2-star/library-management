<?php

namespace App\Http\Controllers\v1\Central;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Services\CentralAuthService;
use App\Services\Payments\EsewaService;
use App\Services\Payments\KhaltiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class CentralSubscriptionPaymentController extends Controller
{
    public function __construct(
        protected CentralAuthService $centralAuthService,
        protected KhaltiService $khaltiService,
        protected EsewaService $esewaService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['nullable', 'string'],
            'subscription_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'string', 'in:PENDING,SUCCESS,FAILED'],
            'payment_method' => ['nullable', 'string', 'in:CASH,KHALTI,ESEWA'],
        ]);

        $query = SubscriptionPayment::query();

        if (! empty($validated['tenant_id'])) {
            $query->where('tenant_id', $validated['tenant_id']);
        }

        if (! empty($validated['subscription_id'])) {
            $query->where('subscription_id', $validated['subscription_id']);
        }

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (! empty($validated['payment_method'])) {
            $query->where('payment_method', $validated['payment_method']);
        }

        $payments = $query->with(['subscription.plan', 'tenant', 'invoice'])
            ->latest('id')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Subscription payments retrieved successfully.',
            'data' => $payments,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subscription_id' => ['required', 'integer', 'exists:subscriptions,id'],
            'payment_method' => ['required', 'string', 'in:CASH,KHALTI,ESEWA'],
        ]);

        $subscription = Subscription::with('plan')->findOrFail($data['subscription_id']);

        if (! $subscription->plan) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription plan not found.',
            ], 422);
        }

        if (! $subscription->plan->price || (float) $subscription->plan->price <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription plan price must be greater than 0.',
            ], 422);
        }

        $pricingPlan = (float) $subscription->plan->price;

        $invoice = SubscriptionInvoice::create([
            'tenant_id' => $subscription->tenant_id,
            'subscription_id' => $subscription->id,
            'invoice_number' => SubscriptionInvoice::generateInvoiceNumber(),
            'invoice_type' => 'subscription',
            'subtotal' => $pricingPlan,
            'tax' => 0,
            'discount' => 0,
            'total_amount' => $pricingPlan,
            'paid_amount' => 0,
            'remaining_amount' => $pricingPlan,
            'status' => 'unpaid',
            'due_date' => now()->addDays(7)->toDateString(),
        ]);

        $payment = SubscriptionPayment::create([
            'subscription_id' => $subscription->id,
            'invoice_id' => $invoice->id,
            'tenant_id' => $subscription->tenant_id,
            'amount' => $pricingPlan,
            'payment_method' => strtoupper($data['payment_method']),
            'status' => 'PENDING',
        ]);

        if (strtoupper($data['payment_method']) === 'CASH') {
            $this->centralAuthService->completeCentralCashPayment($payment);
        }

        return response()->json([
            'success' => true,
            'message' => 'Invoice and subscription payment created successfully.',
            'data' => $payment->fresh()->load(['subscription.plan', 'tenant', 'invoice']),
        ], 201);
    }

    public function initiateFromPlan(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subscription_plan_id' => ['required', 'integer', 'exists:subscription_plans,id'],
            'payment_method' => ['required', 'string', 'in:CASH,KHALTI,ESEWA'],
        ]);

        $plan = SubscriptionPlan::where('id', $data['subscription_plan_id'])
            ->where('is_active', true)
            ->first();

        if (! $plan) {
            return response()->json([
                'success' => false,
                'message' => 'Selected subscription plan is not available.',
            ], 422);
        }

        if (! $plan->price || (float) $plan->price <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription plan price must be greater than 0.',
            ], 422);
        }

        $subscription = Subscription::create([
            'tenant_id' => null,
            'subscription_plan_id' => $plan->id,
            'amount' => (float) $plan->price,
            'starts_at' => null,
            'expires_at' => null,
            'status' => 'pending',
        ]);

        $pricingPlan = (float) $plan->price;

        $invoice = SubscriptionInvoice::create([
            'tenant_id' => null,
            'subscription_id' => $subscription->id,
            'invoice_number' => SubscriptionInvoice::generateInvoiceNumber(),
            'invoice_type' => 'subscription',
            'subtotal' => $pricingPlan,
            'tax' => 0,
            'discount' => 0,
            'total_amount' => $pricingPlan,
            'paid_amount' => 0,
            'remaining_amount' => $pricingPlan,
            'status' => 'unpaid',
            'due_date' => now()->addDays(7)->toDateString(),
        ]);

        $payment = SubscriptionPayment::create([
            'subscription_id' => $subscription->id,
            'invoice_id' => $invoice->id,
            'tenant_id' => null,
            'amount' => $pricingPlan,
            'payment_method' => strtoupper($data['payment_method']),
            'status' => 'PENDING',
        ]);

        if (strtoupper($data['payment_method']) === 'KHALTI') {
            try {
                $result = $this->khaltiService->initiateSubscription($payment);
                return response()->json([
                    'success' => true,
                    'message' => 'Invoice created and Khalti payment initiated.',
                    'data' => [
                        'subscription' => $subscription->load('plan'),
                        'invoice' => $invoice->load('subscription.plan'),
                        'subscription_payment' => $payment->load(['subscription.plan', 'invoice']),
                        'khalti' => $result,
                    ],
                ], 201);
            } catch (Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Khalti initiation failed: '.$e->getMessage(),
                ], 500);
            }
        }

        if (strtoupper($data['payment_method']) === 'ESEWA') {
            try {
                $result = $this->esewaService->initiateSubscription($payment);
                return response()->json([
                    'success' => true,
                    'message' => 'Invoice created and eSewa payment initiated.',
                    'data' => [
                        'subscription' => $subscription->load('plan'),
                        'invoice' => $invoice->load('subscription.plan'),
                        'subscription_payment' => $payment->load(['subscription.plan', 'invoice']),
                        'esewa' => $result,
                    ],
                ], 201);
            } catch (Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'eSewa initiation failed: '.$e->getMessage(),
                ], 500);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Invoice and subscription payment created successfully. Proceed to payment.',
            'data' => [
                'subscription' => $subscription->load('plan'),
                'invoice' => $invoice->load('subscription.plan'),
                'subscription_payment' => $payment->load(['subscription.plan', 'invoice']),
            ],
        ], 201);
    }

    public function completeAndCreateTenant(Request $request, SubscriptionPayment $payment): JsonResponse
    {
        $data = $request->validate([
            'owner' => ['required', 'string', 'max:255'],
            'company_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'subdomain' => ['required', 'string', 'max:255', 'unique:tenants,tenant_code'],
        ]);

        if ($payment->status !== 'PENDING') {
            return response()->json([
                'success' => false,
                'message' => 'Payment must be in PENDING status.',
            ], 422);
        }

        $result = $this->centralAuthService->completePaymentAndCreateTenant($payment, $data);

        return response()->json([
            'success' => true,
            'message' => 'Tenant created and subscription activated successfully.',
            'data' => $result,
        ], 201);
    }

    public function verifyKhalti(SubscriptionPayment $payment): JsonResponse
    {
        if ($payment->payment_method !== 'KHALTI') {
            return response()->json([
                'success' => false,
                'message' => 'Invalid payment method for Khalti.',
            ], 400);
        }

        try {
            if (in_array($payment->status, ['SUCCESS', 'COMPLETED'], true)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment already completed.',
                    'data' => $payment->fresh()->load(['subscription.plan', 'tenant', 'invoice']),
                ]);
            }

            $result = $this->khaltiService->verify($payment->transaction_id);

            if (in_array($result['status'], ['Completed', 'SUCCESS'], true)) {
                $payment->update([
                    'gateway_response' => $result['gateway_response'] ?? null,
                    'transaction_id' => $result['transaction_id'] ?? $payment->transaction_id,
                ]);

                $this->centralAuthService->completeCentralCashPayment($payment);

                return response()->json([
                    'success' => true,
                    'message' => 'Khalti payment completed successfully.',
                    'data' => $payment->fresh()->load(['subscription.plan', 'tenant', 'invoice']),
                ]);
            }

            if ($result['status'] === 'Pending') {
                $payment->update([
                    'status' => 'PENDING',
                    'gateway_response' => $result['gateway_response'] ?? null,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Khalti payment is still pending.',
                    'data' => $payment->fresh()->load(['subscription.plan', 'tenant', 'invoice']),
                ]);
            }

            $payment->update([
                'status' => 'FAILED',
                'gateway_response' => $result['gateway_response'] ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Khalti payment failed or was cancelled.',
            ], 400);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function verifyEsewa(Request $request, SubscriptionPayment $payment): JsonResponse
    {
        if ($payment->payment_method !== 'ESEWA') {
            return response()->json([
                'success' => false,
                'message' => 'Invalid payment method for eSewa.',
            ], 400);
        }

        try {
            if (in_array($payment->status, ['SUCCESS', 'COMPLETED'], true)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment already completed.',
                    'data' => $payment->fresh()->load(['subscription.plan', 'tenant', 'invoice']),
                ]);
            }

            $result = $this->esewaService->verify($request->all());

            if (in_array($result['status'], ['Completed', 'SUCCESS'], true)) {
                $payment->update([
                    'gateway_response' => $result['gateway_response'] ?? null,
                    'transaction_id' => $result['transaction_id'] ?? $payment->transaction_id,
                ]);

                $this->centralAuthService->completeCentralCashPayment($payment);

                return response()->json([
                    'success' => true,
                    'message' => 'eSewa payment completed successfully.',
                    'data' => $payment->fresh()->load(['subscription.plan', 'tenant', 'invoice']),
                ]);
            }

            $payment->update([
                'status' => 'FAILED',
                'gateway_response' => $result['gateway_response'] ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'eSewa payment failed or was cancelled.',
            ], 400);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(SubscriptionPayment $payment): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $payment->load(['subscription', 'tenant', 'invoice']),
        ]);
    }

    public function complete(SubscriptionPayment $payment): JsonResponse
    {
        $this->centralAuthService->completeCentralCashPayment($payment);

        return response()->json([
            'success' => true,
            'message' => 'Payment completed successfully.',
            'data' => $payment->fresh()->load(['subscription', 'tenant', 'invoice']),
        ]);
    }

    /**
     * Pay the pending subscription payment created during tenant registration.
     *
     * The tenant is created as "inactive" during registration. This endpoint
     * takes the already existing PENDING payment and lets the caller settle it
     * with CASH, KHALTI or ESEWA. On success the existing activation logic
     * (CentralAuthService::completeCentralCashPayment) flips the subscription
     * to active and the tenant to active.
     */
    public function pay(Request $request, SubscriptionPayment $payment): JsonResponse
    {
        $data = $request->validate([
            'payment_method' => ['required', 'string', 'in:CASH,KHALTI,ESEWA'],
        ]);

        $payment->load(['subscription.plan', 'tenant.domains', 'invoice']);

        if (! $payment->tenant) {
            return response()->json([
                'success' => false,
                'message' => 'This payment is not linked to a tenant. Use complete-and-create-tenant instead.',
            ], 422);
        }

        if (in_array($payment->status, ['SUCCESS', 'COMPLETED'], true)) {
            return response()->json([
                'success' => true,
                'message' => 'Subscription payment already completed.',
                'data' => $this->buildPaymentSummary($payment),
            ]);
        }

        $method = strtoupper($data['payment_method']);

        if ($method === 'CASH') {
            $this->centralAuthService->completeCentralCashPayment($payment);

            return response()->json([
                'success' => true,
                'message' => 'Cash payment recorded. Subscription activated and tenant is now active.',
                'data' => $this->buildPaymentSummary($payment->fresh(['subscription.plan', 'tenant.domains', 'invoice'])),
            ]);
        }

        /*
        | Allow retrying a previously failed gateway payment.
        */
        if ($payment->status === 'FAILED') {
            $payment->update([
                'status' => 'PENDING',
                'paid_at' => null,
            ]);
        }

        $payment->update([
            'payment_method' => $method,
        ]);

        try {
            $gateway = $method === 'KHALTI'
                ? $this->khaltiService->initiateSubscription($payment)
                : $this->esewaService->initiateSubscription($payment);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $method.' initiation failed: '.$e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => $method.' payment initiated. Complete the payment on the gateway to activate the tenant.',
            'data' => $this->buildPaymentSummary(
                $payment->fresh(['subscription.plan', 'tenant.domains', 'invoice']),
                $gateway
            ),
        ]);
    }

    /**
     * Public status endpoint so a frontend can poll payment / tenant state
     * after the gateway redirects the user back.
     */
    public function paymentStatus(SubscriptionPayment $payment): JsonResponse
    {
        $payment->load(['subscription.plan', 'tenant.domains', 'invoice']);

        return response()->json([
            'success' => true,
            'message' => 'Subscription payment status retrieved successfully.',
            'data' => $this->buildPaymentSummary($payment),
        ]);
    }

    /**
     * Build the "how to pay" payload for a subscription payment.
     */
    protected function buildPaymentSummary(
        SubscriptionPayment $payment,
        ?array $gateway = null
    ): array {
        $payment->loadMissing(['subscription.plan', 'tenant.domains', 'invoice']);

        $tenant = $payment->tenant;
        $isPaid = in_array($payment->status, ['SUCCESS', 'COMPLETED'], true);
        $isTenantActive = $tenant && $tenant->status === 'active';

        $domain = $tenant?->domains?->first()?->domain;

        $summary = [
            'payment' => [
                'id' => $payment->id,
                'amount' => $payment->amount,
                'payment_method' => $payment->payment_method,
                'status' => $payment->status,
                'transaction_id' => $payment->transaction_id,
                'paid_at' => $payment->paid_at,
            ],
            'invoice' => $payment->invoice ? [
                'id' => $payment->invoice->id,
                'invoice_number' => $payment->invoice->invoice_number,
                'total_amount' => $payment->invoice->total_amount,
                'paid_amount' => $payment->invoice->paid_amount,
                'remaining_amount' => $payment->invoice->remaining_amount,
                'status' => $payment->invoice->status,
                'due_date' => $payment->invoice->due_date,
            ] : null,
            'subscription' => $payment->subscription ? [
                'id' => $payment->subscription->id,
                'status' => $payment->subscription->status,
                'starts_at' => $payment->subscription->starts_at,
                'expires_at' => $payment->subscription->expires_at,
                'plan' => $payment->subscription->plan ? [
                    'id' => $payment->subscription->plan->id,
                    'name' => $payment->subscription->plan->name,
                    'price' => $payment->subscription->plan->price,
                    'duration' => $payment->subscription->plan->duration,
                    'duration_unit' => $payment->subscription->plan->duration_unit,
                ] : null,
            ] : null,
            'tenant' => $tenant ? [
                'id' => $tenant->id,
                'company_name' => $tenant->company_name,
                'tenant_code' => $tenant->tenant_code,
                'status' => $tenant->status,
                'domain' => $domain,
            ] : null,
            'is_paid' => $isPaid,
            'is_tenant_active' => $isTenantActive,
        ];

        if ($gateway) {
            $summary['gateway'] = $gateway;
        }

        if ($isTenantActive) {
            $summary['next_step'] = 'Payment successful. The tenant is active and can now log in.';

            $summary['login'] = [
                'url' => $domain ? 'https://'.$domain.'/login' : null,
                'method' => 'POST',
            ];
        } else {
            $summary['next_step'] = 'Complete the pending payment to activate the tenant.';

            $summary['pay'] = [
                'method' => 'POST',
                'url' => route(
                    'central.subscription-payments.pay',
                    ['payment' => $payment->id]
                ),
                'allowed_payment_methods' => ['CASH', 'KHALTI', 'ESEWA'],
            ];

            $summary['status_url'] = route(
                'central.subscription-payments.status',
                ['payment' => $payment->id]
            );
        }

        return $summary;
    }

    public function fail(SubscriptionPayment $payment): JsonResponse
    {
        if (in_array($payment->status, ['SUCCESS', 'COMPLETED'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot fail an already successful payment.',
            ], 422);
        }

        $payment->update([
            'status' => 'FAILED',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment marked as failed.',
            'data' => $payment->fresh()->load(['subscription', 'tenant', 'invoice']),
        ]);
    }
}
