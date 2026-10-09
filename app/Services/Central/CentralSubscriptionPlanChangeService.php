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

    public function preview(int $subscriptionId, int $planId): array
    {
        $current = $this->subscriptionRepository->find($subscriptionId);

        if (! $current) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
        }

        if ($current->status === 'cancelled') {
            throw new InvalidArgumentException(
                'Cannot change plan for a cancelled subscription.'
            );
        }

        $current->loadMissing(['plan', 'tenant']);
        $newPlan = SubscriptionPlan::query()->findOrFail($planId);
        $effectiveDate = now()->startOfDay();
        $financials = $this->calculateCurrentPlanBalance($current, $effectiveDate);
        $settlementRequired = $financials['outstanding'] > 0;
        $credit = $settlementRequired ? 0.0 : $financials['credit'];
        $newPlanPrice = round((float) $newPlan->price, 2);
        $newAmountDue = max(0, round($newPlanPrice - $credit, 2));
        $newExpiresAt = $this->centralSubscriptionService
            ->expiryDateForPlan($effectiveDate, $newPlan);

        $settlementInvoice = $current->invoices()
            ->where('invoice_type', 'plan_change_settlement')
            ->whereIn('status', ['unpaid', 'partially_paid', 'overdue'])
            ->latest('id')
            ->first();
        $previousInvoices = $current->invoices()
            ->whereIn('invoice_type', ['subscription', 'renewal'])
            ->latest('id')
            ->get()
            ->map(fn (CentralInvoice $invoice) => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'invoice_type' => $invoice->invoice_type,
                'total_amount' => $invoice->total_amount,
                'paid_amount' => $invoice->paid_amount,
                'remaining_amount' => $invoice->remaining_amount,
                'status' => $invoice->status,
            ])
            ->values();

        return [
            'effective_date' => $effectiveDate->toDateString(),
            'can_change_plan' => ! $settlementRequired,
            'settlement_required' => $settlementRequired,
            'current_plan' => [
                'subscription_id' => $current->id,
                'plan_id' => $current->plan?->id,
                'plan_name' => $current->plan?->name,
                'accrued_charges' => $financials['accrued'],
                'amount_paid' => $financials['paid'],
                'outstanding_amount' => $financials['outstanding'],
                'eligible_credit' => $credit,
                'invoices' => $previousInvoices,
            ],
            'settlement_invoice' => $settlementInvoice
                ? [
                    'id' => $settlementInvoice->id,
                    'invoice_number' => $settlementInvoice->invoice_number,
                    'status' => $settlementInvoice->status,
                    'remaining_amount' => $settlementInvoice->remaining_amount,
                ]
                : null,
            'new_plan' => [
                'id' => $newPlan->id,
                'name' => $newPlan->name,
                'price' => $newPlanPrice,
                'credit_applied' => $credit,
                'amount_due' => $newAmountDue,
                'duration' => $newPlan->duration,
                'duration_unit' => $newPlan->duration_unit,
                'starts_at' => $effectiveDate->toDateString(),
                'expires_at' => $newExpiresAt->toDateString(),
                'activation_condition' => $newAmountDue <= 0
                    ? 'immediate'
                    : 'first_successful_positive_payment',
            ],
        ];
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
                'accrued' => 0.0,
                'paid' => $paid,
                'outstanding' => 0.0,
                'credit' => 0.0,
            ];
        }

        $startsAt = Carbon::parse($subscription->starts_at)->startOfDay();
        $expiresAt = Carbon::parse($subscription->expires_at)->startOfDay();
        $termDays = $startsAt->diffInDays($expiresAt);

        if ($termDays <= 0) {
            return [
                'accrued' => 0.0,
                'paid' => $paid,
                'outstanding' => 0.0,
                'credit' => 0.0,
            ];
        }

        $price = round((float) ($subscription->amount ?? $subscription->plan?->price ?? 0), 2);
        $serviceEnd = $effectiveDate->greaterThan($expiresAt)
            ? $expiresAt
            : $effectiveDate;
        $elapsedDays = $serviceEnd->lessThan($startsAt)
            ? 0
            : $startsAt->diffInDays($serviceEnd) + 1;
        $usedDays = min($termDays, $elapsedDays);
        $remainingDays = max(0, $termDays - $usedDays);

        $accrued = round($price * ($usedDays / $termDays), 2);
        $outstanding = round(max(0, $accrued - $paid), 2);
        $unpaidCredit = max(0, $paid - $accrued);
        $remainingPeriodValue = round($price * ($remainingDays / $termDays), 2);
        $credit = round(min($unpaidCredit, $remainingPeriodValue), 2);

        return [
            'accrued' => $accrued,
            'paid' => $paid,
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
