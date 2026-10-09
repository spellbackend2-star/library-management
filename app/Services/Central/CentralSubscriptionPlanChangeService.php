<?php

namespace App\Services\Central;

use App\Models\CentralInvoice;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Repositories\Interface\SubscriptionInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CentralSubscriptionPlanChangeService
{
    public function __construct(
        protected SubscriptionInterface $subscriptionRepository,
        protected CentralInvoiceService $invoiceService,
        protected CentralSubscriptionService $centralSubscriptionService
    ) {}

    public function changePlan(int $subscriptionId, int $planId): Subscription
    {
        $result = DB::transaction(function () use ($subscriptionId, $planId) {
            $current = $this->subscriptionRepository->findForUpdate($subscriptionId);

            if ($current->status === 'cancelled') {
                throw new InvalidArgumentException(
                    'Cannot change plan for a cancelled subscription.'
                );
            }

            $current->loadMissing(['plan', 'tenant']);
            $newPlan = SubscriptionPlan::query()->findOrFail($planId);
            $effectiveDate = now()->startOfDay();
            $financials = $this->calculateCurrentPlanBalance($current, $effectiveDate);

            if ($financials['outstanding'] > 0) {
                $settlementInvoice = $this->getOrCreateSettlementInvoice(
                    $current,
                    $financials['outstanding']
                );

                return ['settlement_invoice' => $settlementInvoice];
            }

            $this->cancelOpenPlanInvoices($current);
            $this->closeOpenSettlementInvoices($current);

            $credit = $financials['credit'];
            $newSubscription = $this->centralSubscriptionService
                ->createPendingSubscription(
                    $current->tenant,
                    $newPlan,
                    $current->id,
                    $effectiveDate
                );

            $invoice = $this->invoiceService->createPlanChangeInvoice(
                $newSubscription,
                $newPlan,
                $credit
            );

            if ((float) $invoice->remaining_amount <= 0) {
                $this->centralSubscriptionService->activateSubscription(
                    $newSubscription,
                    $newPlan,
                    true
                );
                $this->centralSubscriptionService->retireReplacedSubscription(
                    $newSubscription
                );
            }

            return $newSubscription->fresh([
                'plan',
                'tenant',
                'invoices',
                'payments',
            ]);
        });

        if (is_array($result) && isset($result['settlement_invoice'])) {
            throw new PlanChangeSettlementRequired(
                $result['settlement_invoice']->load('subscription.plan')
            );
        }

        return $result;
    }

    /**
     * Prorate the subscription's locked-in amount over its actual service
     * period. Only successful payments count toward the accrued charges.
     */
    private function calculateCurrentPlanBalance(
        Subscription $subscription,
        Carbon $effectiveDate
    ): array {
        $paid = round((float) $subscription->payments()
            ->whereIn('status', ['SUCCESS', 'COMPLETED'])
            ->sum('amount'), 2);

        if (! $subscription->starts_at || ! $subscription->expires_at) {
            return [
                'outstanding' => 0.0,
                'credit' => 0.0,
            ];
        }

        $startsAt = Carbon::parse($subscription->starts_at)->startOfDay();
        $expiresAt = Carbon::parse($subscription->expires_at)->startOfDay();
        $termDays = $startsAt->diffInDays($expiresAt);

        if ($termDays <= 0) {
            return [
                'outstanding' => 0.0,
                'credit' => 0.0,
            ];
        }

        $price = round((float) ($subscription->amount ?? $subscription->plan?->price ?? 0), 2);
        $serviceEnd = $effectiveDate->greaterThan($expiresAt)
            ? $expiresAt
            : $effectiveDate;
        $usedDays = $serviceEnd->lessThanOrEqualTo($startsAt)
            ? 0
            : min($termDays, $startsAt->diffInDays($serviceEnd));
        $remainingDays = $effectiveDate->greaterThanOrEqualTo($expiresAt)
            ? 0
            : max(0, $effectiveDate->greaterThan($startsAt)
                ? $effectiveDate->diffInDays($expiresAt)
                : $termDays);

        $accrued = round($price * ($usedDays / $termDays), 2);
        $outstanding = round(max(0, $accrued - $paid), 2);
        $unpaidCredit = max(0, $paid - $accrued);
        $remainingPeriodValue = round($price * ($remainingDays / $termDays), 2);
        $credit = round(min($unpaidCredit, $remainingPeriodValue), 2);

        return [
            'outstanding' => $outstanding,
            'credit' => $credit,
        ];
    }

    private function cancelOpenPlanInvoices(Subscription $subscription): void
    {
        $subscription->invoices()
            ->whereIn('invoice_type', ['subscription', 'renewal'])
            ->whereIn('status', ['unpaid', 'partially_paid', 'overdue'])
            ->update(['status' => 'cancelled']);
    }

    private function getOrCreateSettlementInvoice(
        Subscription $subscription,
        float $outstanding
    ): CentralInvoice {
        $invoice = $subscription->invoices()
            ->where('invoice_type', 'plan_change_settlement')
            ->whereIn('status', ['unpaid', 'partially_paid', 'overdue'])
            ->latest('id')
            ->first();

        if (! $invoice) {
            return $this->invoiceService->createPlanChangeSettlementInvoice(
                $subscription,
                $outstanding
            );
        }

        $paidAmount = round((float) $invoice->paid_amount, 2);
        $invoice->update([
            'subtotal' => round($paidAmount + $outstanding, 2),
            'total_amount' => round($paidAmount + $outstanding, 2),
            'remaining_amount' => $outstanding,
            'status' => $paidAmount > 0 ? 'partially_paid' : 'unpaid',
        ]);

        return $invoice->fresh();
    }

    private function closeOpenSettlementInvoices(Subscription $subscription): void
    {
        $subscription->invoices()
            ->where('invoice_type', 'plan_change_settlement')
            ->whereIn('status', ['unpaid', 'partially_paid', 'overdue'])
            ->update([
                'total_amount' => DB::raw('paid_amount'),
                'remaining_amount' => 0,
                'status' => 'paid',
            ]);
    }
}
