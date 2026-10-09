<?php

namespace App\Services\Central;

use App\Models\CentralInvoice;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionPayment;
use App\Repositories\Interface\CentralInvoiceInterface;
use InvalidArgumentException;

class CentralInvoiceService
{
    public function __construct(
        protected CentralInvoiceInterface $centralInvoiceRepository
    ) {}

    public function getAll(array $filters = [])
    {
        return $this->centralInvoiceRepository->getAll($filters);
    }

    public function getById(int $id): ?CentralInvoice
    {
        return $this->centralInvoiceRepository->find($id);
    }

    public function getByNumber(string $invoiceNumber): ?CentralInvoice
    {
        return $this->centralInvoiceRepository->findByNumber($invoiceNumber);
    }

    /**
     * Create a central invoice for a subscription payment. The plan name
     * is snapshotted into the notes so the invoice keeps showing what was
     * billed even if the plan is renamed later.
     */
    public function createForPayment(int $subscriptionPaymentId, array $overrides = []): CentralInvoice
    {
        $payment = SubscriptionPayment::with('subscription.plan')
            ->find($subscriptionPaymentId);

        if (! $payment) {
            throw new InvalidArgumentException('Subscription payment not found.');
        }

        $subtotal = (float) ($overrides['subtotal'] ?? $payment->amount ?? 0);
        $tax = (float) ($overrides['tax'] ?? 0);
        $discount = (float) ($overrides['discount'] ?? 0);
        $couponDiscount = (float) ($overrides['coupon_discount'] ?? 0);
        $total = round($subtotal + $tax - $discount - $couponDiscount, 2);
        $paid = (float) ($overrides['paid_amount'] ?? 0);

        $planName = $payment->subscription?->plan?->name;

        return $this->centralInvoiceRepository->create([
            'tenant_id' => $payment->tenant_id,
            'subscription_id' => $payment->subscription_id,
            'subscription_payment_id' => $payment->id,
            'invoice_number' => CentralInvoice::generateInvoiceNumber(),
            'invoice_type' => $overrides['invoice_type'] ?? 'subscription',
            'subtotal' => $subtotal,
            'tax' => $tax,
            'discount' => $discount,
            'coupon_id' => $overrides['coupon_id'] ?? null,
            'coupon_discount' => $couponDiscount,
            'total_amount' => $total,
            'paid_amount' => $paid,
            'remaining_amount' => max(0, round($total - $paid, 2)),
            'currency' => $overrides['currency'] ?? 'NPR',
            'currency_symbol' => $overrides['currency_symbol'] ?? 'Rs.',
            'status' => $paid > 0 ? 'partially_paid' : 'unpaid',
            'due_date' => $overrides['due_date'] ?? now()->addDays(7),
            'notes' => $overrides['notes'] ?? ($planName
                ? 'Invoice for plan: '.$planName
                : null),
        ]);
    }

    public function createSubscriptionInvoice(
        Subscription $subscription,
        string $tenantId,
        float $amount,
        ?int $couponId = null,
        float $couponDiscount = 0
    ): CentralInvoice {
        $totalAmount = round($amount - $couponDiscount, 2);

        return $this->create([
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

    public function createPlanChangeSettlementInvoice(
        Subscription $subscription,
        float $amount
    ): CentralInvoice {
        return $this->create([
            'tenant_id' => $subscription->tenant_id,
            'subscription_id' => $subscription->id,
            'invoice_number' => CentralInvoice::generateInvoiceNumber(),
            'invoice_type' => 'plan_change_settlement',
            'subtotal' => $amount,
            'tax' => 0,
            'discount' => 0,
            'coupon_discount' => 0,
            'total_amount' => $amount,
            'paid_amount' => 0,
            'remaining_amount' => $amount,
            'status' => 'unpaid',
            'due_date' => now()->addDays(7)->toDateString(),
            'notes' => 'Outstanding charges due before changing subscription plans.',
        ]);
    }

    public function createPlanChangeInvoice(
        Subscription $subscription,
        SubscriptionPlan $plan,
        float $credit
    ): CentralInvoice {
        $price = round((float) $plan->price, 2);
        $credit = min($price, round($credit, 2));
        $due = max(0, round($price - $credit, 2));

        return $this->create([
            'tenant_id' => $subscription->tenant_id,
            'subscription_id' => $subscription->id,
            'invoice_number' => CentralInvoice::generateInvoiceNumber(),
            'invoice_type' => 'plan_change',
            'subtotal' => $price,
            'tax' => 0,
            'discount' => $credit,
            'coupon_discount' => 0,
            'total_amount' => $due,
            'paid_amount' => 0,
            'remaining_amount' => $due,
            'status' => $due <= 0 ? 'paid' : 'unpaid',
            'due_date' => now()->addDays(7)->toDateString(),
            'notes' => 'Plan-change invoice for '.$plan->name,
        ]);
    }

    public function create(array $data): CentralInvoice
    {
        return CentralInvoice::create($data);
    }

    public function update(int $id, array $data): CentralInvoice
    {
        return $this->centralInvoiceRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->centralInvoiceRepository->delete($id);
    }
}
