<?php

namespace App\Services\Central;

use App\Models\CentralInvoice;
use App\Models\SubscriptionPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CentralPaymentService
{
    public function __construct(protected CentralSubscriptionService $centralSubscriptionService) {}

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

            // A successful installment starts the subscription immediately.
            // The invoice remains partially paid until the balance is settled.
            $this->centralSubscriptionService->activateSubscription($subscription, $plan, true);

            $tenant = $payment->tenant()->first() ?? $subscription->tenant()->first();

            if (
                $tenant
                && ! in_array($tenant->status, ['pending', 'rejected'], true)
            ) {
                $tenant->update([
                    'status' => 'active',
                    'suspension_reason' => null,
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

    public function activateSubscriptionPayment(SubscriptionPayment $payment): SubscriptionPayment
    {
        return $this->completeCentralCashPayment($payment);
    }
}
