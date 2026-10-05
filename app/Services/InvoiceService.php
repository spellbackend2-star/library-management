<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Setting;
use App\Repositories\Interface\InvoiceInterface;
use App\Repositories\Interface\PaymentRepositoryInterface;
use App\Services\FineService;
use App\Services\Payments\EsewaService;
use App\Services\Payments\KhaltiService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceService
{
    public function __construct(
        protected InvoiceInterface $invoiceRepository,
        protected PaymentRepositoryInterface $paymentRepository,
    ) {}

    public function getAll(array $filters = [])
    {
        return $this->invoiceRepository->getAll($filters);
    }

    public function findById(int $id): ?Invoice
    {
        return $this->invoiceRepository->find($id);
    }

    public function findByMember(int $memberId): ?Invoice
    {
        return $this->invoiceRepository->findByMember($memberId);
    }

    public function fineInvoiceByMember(int $memberId): ?Invoice
    {
        return $this->invoiceRepository->fineInvoiceByMember($memberId);
    }

    /**
     * Create an invoice for a member.
     *
     * The package price is ALWAYS added by this service.
     * $data['total_amount'] is treated as EXTRA charges only (default 0),
     * so do not pass the package price from the controller.
     */
    public function create(array $data): Invoice
    {
        $memberId = (int) $data['member_id'];
        $couponId = isset($data['coupon_id']) ? (int) $data['coupon_id'] : null;
        $extraAmount = round((float) ($data['extra_amount'] ?? 0), 2);

        // Runs inside the caller's transaction if one exists (nested-safe).
        return DB::transaction(function () use ($memberId, $couponId, $extraAmount, $data) {
            $existing = $this->invoiceRepository->findByMember($memberId);

            if ($existing) {
                throw new \Exception('Member already has an unpaid invoice.');
            }

            $member = Member::findOrFail($memberId);
            $package = $member->package;

            if (! $package) {
                throw new \Exception('Member does not have a package assigned.');
            }

            $totalAmount = round((float) $package->price + $extraAmount, 2);

            // Coupon is validated and consumed in the same transaction as the
            // invoice, so a failure rolls the usage count back too.
            $couponDiscount = $this->applyCouponIfProvided($couponId, $totalAmount);

            if ($couponDiscount > 0) {
                $totalAmount = round($totalAmount - $couponDiscount, 2);
            }

            $invoiceNumber = $this->generateInvoiceNumber();

            return $this->invoiceRepository->create([
                'member_id' => $memberId,
                'coupon_id' => $couponId,
                'invoice_number' => $invoiceNumber,
                'invoice_type' => $data['invoice_type'] ?? 'booking',
                'total_amount' => $totalAmount,
                'coupon_discount' => $couponDiscount,
                'paid_amount' => 0,
                'remaining_amount' => $totalAmount,
                'status' => 'unpaid',
                'due_date' => $data['due_date'] ?? now()->addDays(7)->toDateString(),
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }

    private function applyCouponIfProvided(?int $couponId, float $totalAmount): float
    {
        if (! $couponId) {
            return 0.0;
        }

        // Lock the row so two requests cannot both use the last remaining use.
        $coupon = Coupon::lockForUpdate()->find($couponId);

        if (! $coupon) {
            throw ValidationException::withMessages([
                'coupon_id' => ['The selected coupon is invalid.'],
            ]);
        }

        if (! $coupon->is_active) {
            throw ValidationException::withMessages([
                'coupon_id' => ['This coupon is inactive.'],
            ]);
        }

        if ($coupon->valid_from && now() < $coupon->valid_from) {
            throw ValidationException::withMessages([
                'coupon_id' => ['This coupon is not yet valid.'],
            ]);
        }

        if ($coupon->valid_until && now() > $coupon->valid_until) {
            throw ValidationException::withMessages([
                'coupon_id' => ['This coupon has expired.'],
            ]);
        }

        if ($coupon->max_uses !== null && (int) $coupon->used_count >= (int) $coupon->max_uses) {
            throw ValidationException::withMessages([
                'coupon_id' => ['Coupon usage limit reached.'],
            ]);
        }

        if ($totalAmount < (float) $coupon->min_order_value) {
            throw ValidationException::withMessages([
                'coupon_id' => ['Minimum order value not met for this coupon.'],
            ]);
        }

        $discount = match (strtoupper((string) $coupon->discount_type)) {
            'PERCENT' => round($totalAmount * ((float) $coupon->discount_value / 100), 2),
            'FLAT' => round((float) $coupon->discount_value, 2),
            default => 0.0,
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
        $extraDiscount = round((float) ($paymentData['extra_discount'] ?? 0), 2);
        $paymentMethod = strtoupper($paymentData['payment_method'] ?? 'CASH');

        // Maximum payable = total_amount (coupon already applied to total_amount)
        $maxPayable = round((float) $invoice->total_amount, 2);
        // Already paid (only SUCCESS payments)
        $paidAmount = round((float) $invoice->paid_amount, 2);
        // Remaining balance
        $remaining = max(0, $maxPayable - $paidAmount);

        if ($extraDiscount > $remaining) {
            throw new \Exception("Extra discount ({$extraDiscount}) exceeds remaining balance ({$remaining}). Max payable: {$maxPayable}, Already paid: {$paidAmount}");
        }

        if ($extraDiscount > 0) {
            $amount = $remaining - $extraDiscount;
        }

        if ($amount <= 0) {
            throw new \Exception('Payment amount must be greater than 0.');
        }

        if ($amount > $remaining) {
            throw new \Exception("Payment amount ({$amount}) exceeds remaining balance ({$remaining}). Max payable: {$maxPayable}, Already paid: {$paidAmount}");
        }

        $isGateway = in_array($paymentMethod, ['KHALTI', 'ESEWA'], true);
        $returnUrl = $paymentData['return_url'] ?? null;

        return DB::transaction(function () use ($invoice, $paymentData, $amount, $extraDiscount, $paymentMethod, $isGateway, $returnUrl) {
            $payment = $this->paymentRepository->create([
                'invoice_id' => $invoice->id,
                'member_id' => $invoice->member_id,
                'booking_id' => null,
                'amount' => $amount,
                'extra_discount' => $extraDiscount,
                'currency' => $paymentData['currency'] ?? 'NPR',
                'payment_method' => $paymentMethod,
                'status' => $isGateway ? 'PENDING' : 'SUCCESS',
                'payment_date' => now(),
                'paid_at' => $isGateway ? null : now(),
            ]);

            if ($isGateway) {
                if ($paymentMethod === 'KHALTI') {
                    $result = app(KhaltiService::class)->initiate($payment, $returnUrl);
                    $payment->update([
                        'payment_url' => $result['payment_url'] ?? null,
                        'gateway_reference' => $result['pidx'] ?? null,
                        'gateway_response' => $result,
                    ]);
                } elseif ($paymentMethod === 'ESEWA') {
                    $result = app(EsewaService::class)->initiate($payment);
                    $payment->update([
                        'payment_url' => $result['payment_url'] ?? null,
                        'gateway_response' => $result,
                    ]);
                }

                return ['gateway' => true, 'payment' => $payment->fresh()];
            }

            $newPaidAmount = round((float) $invoice->paid_amount + $amount, 2);
            $newRemainingAmount = round((float) $invoice->total_amount - $newPaidAmount, 2);
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

                app(FineService::class)->syncFineStatusOnInvoicePaid($invoice);
            }

            return $payment;
        });
    }

    public function activateMemberPackage(Invoice $invoice): void
    {
        $member = $invoice->member;

        if (! $member) {
            return;
        }

        $package = $member->package;

        if (! $package) {
            return;
        }

        $start = now();

        $expiry = match ($package->duration_unit) {
            'day' => $start->copy()->addDays((int) $package->duration),
            'year' => $start->copy()->addYears((int) $package->duration),
            default => $start->copy()->addMonths((int) $package->duration),
        };

        $member->update([
            'status' => 'active',
            'membership_start' => $start->toDateString(),
            'membership_expiry' => $expiry->toDateString(),
        ]);
    }

    protected function generateInvoiceNumber(): string
    {
        $prefix = strtoupper((string) (Setting::where('group', 'invoice')
            ->where('key', 'invoice_prefix')
            ->value('value') ?: 'INV'));

        $startNumber = (int) (Setting::where('group', 'invoice')
            ->where('key', 'invoice_start_number')
            ->value('value') ?: 1000);

        $format = (string) (Setting::where('group', 'invoice')
            ->where('key', 'invoice_number_format')
            ->value('value') ?: '{PREFIX}-{YEAR}-{NUMBER}');

        // FIX: a format without {NUMBER} can never be unique, and made the
        // while-loop below spin forever. Force the placeholder to exist.
        if (! str_contains($format, '{NUMBER}')) {
            $format .= '-{NUMBER}';
        }

        $year = now()->format('Y');

        $build = fn(int $number): string => str_replace(
            ['{PREFIX}', '{YEAR}', '{NUMBER}'],
            [$prefix, $year, (string) $number],
            $format
        );

        $last = Invoice::orderByDesc('id')->first();
        $next = $last ? ((int) $last->id + 1) : $startNumber;

        if ($next < $startNumber) {
            $next = $startNumber;
        }

        $formatted = $build($next);

        // FIX: hard cap so this can never hang the request again.
        $attempts = 0;

        while (Invoice::where('invoice_number', $formatted)->exists()) {
            if (++$attempts > 1000) {
                throw new \RuntimeException('Could not generate a unique invoice number.');
            }

            $next++;
            $formatted = $build($next);
        }

        return $formatted;
    }
}
