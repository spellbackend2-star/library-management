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
