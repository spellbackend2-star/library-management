<?php

namespace App\Http\Controllers\v1\Central;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
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

        $payments = $query->with(['subscription.plan', 'tenant'])
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

        $payment = SubscriptionPayment::create([
            'subscription_id' => $subscription->id,
            'tenant_id' => $subscription->tenant_id,
            'amount' => (float) $subscription->plan->price,
            'payment_method' => strtoupper($data['payment_method']),
            'status' => 'PENDING',
        ]);

        if (strtoupper($data['payment_method']) === 'CASH') {
            $this->centralAuthService->completeCentralCashPayment($payment);
        }

        return response()->json([
            'success' => true,
            'message' => 'Subscription payment created successfully.',
            'data' => $payment->fresh()->load(['subscription.plan', 'tenant']),
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

        $payment = SubscriptionPayment::create([
            'subscription_id' => $subscription->id,
            'tenant_id' => null,
            'amount' => (float) $plan->price,
            'payment_method' => strtoupper($data['payment_method']),
            'status' => 'PENDING',
        ]);

        if (strtoupper($data['payment_method']) === 'KHALTI') {
            try {
                $result = $this->khaltiService->initiateSubscription($payment);
                return response()->json([
                    'success' => true,
                    'message' => 'Khalti payment initiated.',
                    'data' => [
                        'subscription' => $subscription->load('plan'),
                        'subscription_payment' => $payment->load('subscription.plan'),
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
                    'message' => 'eSewa payment initiated.',
                    'data' => [
                        'subscription' => $subscription->load('plan'),
                        'subscription_payment' => $payment->load('subscription.plan'),
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
            'message' => 'Subscription and payment created successfully. Proceed to payment.',
            'data' => [
                'subscription' => $subscription->load('plan'),
                'subscription_payment' => $payment->load('subscription.plan'),
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
                    'data' => $payment->fresh()->load(['subscription.plan', 'tenant']),
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
                    'data' => $payment->fresh()->load(['subscription.plan', 'tenant']),
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
                    'data' => $payment->fresh()->load(['subscription.plan', 'tenant']),
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
                    'data' => $payment->fresh()->load(['subscription.plan', 'tenant']),
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
                    'data' => $payment->fresh()->load(['subscription.plan', 'tenant']),
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
            'data' => $payment->load(['subscription', 'tenant']),
        ]);
    }

    public function complete(SubscriptionPayment $payment): JsonResponse
    {
        $this->centralAuthService->completeCentralCashPayment($payment);

        return response()->json([
            'success' => true,
            'message' => 'Payment completed successfully.',
            'data' => $payment->fresh()->load(['subscription', 'tenant']),
        ]);
    }
}
