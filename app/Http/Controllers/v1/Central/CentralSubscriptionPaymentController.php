<?php

namespace App\Http\Controllers\v1\Central;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Services\CentralAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CentralSubscriptionPaymentController extends Controller
{
    public function __construct(
        protected CentralAuthService $centralAuthService
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
