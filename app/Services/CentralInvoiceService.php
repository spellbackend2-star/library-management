<?php

namespace App\Services;

use App\Models\CentralInvoice;
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

    public function update(int $id, array $data): CentralInvoice
    {
        return $this->centralInvoiceRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->centralInvoiceRepository->delete($id);
    }
}
