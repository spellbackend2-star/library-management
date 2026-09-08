<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\Payment;
use App\Repositories\Interface\InvoiceInterface;
use App\Repositories\Interface\PaymentRepositoryInterface;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function __construct(
        protected InvoiceInterface $invoiceRepository,
        protected PaymentRepositoryInterface $paymentRepository,
    ) {}

    public function getAll()
    {
        return $this->invoiceRepository->all();
    }

    public function findById(int $id): ?Invoice
    {
        return $this->invoiceRepository->find($id);
    }

    public function findByMember(int $memberId): ?Invoice
    {
        return $this->invoiceRepository->findByMember($memberId);
    }

    public function create(array $data): Invoice
    {
        $memberId = (int) $data['member_id'];
        $totalAmount = round((float) ($data['total_amount'] ?? 0), 2);

        $existing = $this->invoiceRepository->findByMember($memberId);

        if ($existing) {
            throw new \Exception('Member already has an unpaid invoice.');
        }

        $member = Member::findOrFail($memberId);
        $package = $member->package;

        if (! $package) {
            throw new \Exception('Member does not have a package assigned.');
        }

        if ($totalAmount <= 0) {
            $totalAmount = round((float) $package->price, 2);
        }

        $couponDiscount = $this->applyCouponIfProvided($data, $totalAmount);

        $invoiceNumber = $this->generateInvoiceNumber();

        return DB::transaction(function () use ($memberId, $totalAmount, $couponDiscount, $invoiceNumber, $data) {
            return $this->invoiceRepository->create([
                'member_id' => $memberId,
                'invoice_number' => $invoiceNumber,
                'total_amount' => $totalAmount,
                'coupon_discount' => $couponDiscount,
                'paid_amount' => 0,
                'remaining_amount' => round($totalAmount - $couponDiscount, 2),
                'status' => 'unpaid',
                'due_date' => $data['due_date'] ?? now()->addDays(7)->toDateString(),
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }

    private function applyCouponIfProvided(array $data, float $totalAmount): float
    {
        $couponCode = $data['coupon_code'] ?? null;

        if (! $couponCode) {
            return 0;
        }

        $coupon = Coupon::where('code', $couponCode)
            ->where('is_active', true)
            ->where('valid_from', '<=', now())
            ->where('valid_until', '>=', now())
            ->first();

        if (! $coupon) {
            throw new \Exception('Invalid or expired coupon code.');
        }

        if ($totalAmount < (float) $coupon->min_order_value) {
            throw new \Exception('Minimum order value not met for this coupon.');
        }

        if ($coupon->max_uses !== null && (int) $coupon->used_count >= (int) $coupon->max_uses) {
            throw new \Exception('Coupon usage limit reached.');
        }

        $discount = match ($coupon->discount_type) {
            'PERCENT' => round($totalAmount * ((float) $coupon->discount_value / 100), 2),
            'FLAT' => round((float) $coupon->discount_value, 2),
        };

        if ($coupon->max_discount !== null) {
            $discount = min($discount, (float) $coupon->max_discount);
        }

        $discount = min($discount, $totalAmount);

        $coupon->increment('used_count');

        return $discount;
    }

    public function addPayment(int $invoiceId, array $paymentData): Payment|array
    {
        $invoice = $this->invoiceRepository->find($invoiceId);

        if (! $invoice) {
            throw new \Exception('Invoice not found.');
        }

        if ($invoice->status === 'paid') {
            $lastPayment = Payment::where('invoice_id', $invoice->id)
                ->orderByDesc('id')
                ->first();

            if ($lastPayment) {
                return ['status' => 'already_paid', 'payment' => $lastPayment];
            }

            throw new \Exception('Invoice is already fully paid.');
        }

        if (in_array($invoice->status, ['cancelled', 'refunded'], true)) {
            throw new \Exception('Invoice is not open for payment.');
        }

        $amount = round((float) ($paymentData['amount'] ?? 0), 2);

        if ($amount <= 0) {
            throw new \Exception('Payment amount must be greater than 0.');
        }

        $remaining = round((float) $invoice->total_amount - (float) $invoice->paid_amount - (float) $invoice->coupon_discount, 2);

        if ($amount > $remaining) {
            throw new \Exception("Payment amount ({$amount}) exceeds remaining balance ({$remaining}).");
        }

        return DB::transaction(function () use ($invoice, $paymentData, $amount) {
            $payment = $this->paymentRepository->create([
                'invoice_id' => $invoice->id,
                'member_id' => $invoice->member_id,
                'booking_id' => null,
                'amount' => $amount,
                'extra_discount' => $paymentData['extra_discount'] ?? 0,
                'currency' => $paymentData['currency'] ?? 'NPR',
                'payment_method' => strtoupper($paymentData['payment_method']),
                'status' => 'SUCCESS',
                'payment_date' => now(),
                'paid_at' => now(),
            ]);

            $newPaidAmount = round((float) $invoice->paid_amount + $amount, 2);
            $newRemainingAmount = round((float) $invoice->total_amount - $newPaidAmount - (float) $invoice->coupon_discount, 2);
            $newStatus = 'partially_paid';

            if ($newRemainingAmount <= 0) {
                $newStatus = 'paid';
                $newRemainingAmount = 0;
            }

            $this->invoiceRepository->update($invoice->id, [
                'paid_amount' => $newPaidAmount,
                'remaining_amount' => $newRemainingAmount,
                'status' => $newStatus,
            ]);

            if ($newStatus === 'paid') {
                $this->activateMemberPackage($invoice);
            }

            return $payment;
        });
    }

    protected function activateMemberPackage(Invoice $invoice): void
    {
        $member = $invoice->member;

        if (! $member) {
            return;
        }

        $package = $member->package;

        if (! $package) {
            return;
        }

        $member->update([
            'status' => 'active',
            'membership_start' => now()->toDateString(),
            'membership_expiry' => now()->addDays((int) $package->duration)->toDateString(),
        ]);
    }

    protected function generateInvoiceNumber(): string
    {
        $last = Invoice::orderByDesc('id')->first();

        $next = $last ? ((int) $last->id + 1) : 1;

        return 'INV-'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }
}
