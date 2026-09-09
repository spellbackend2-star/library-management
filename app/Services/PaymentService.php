<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Payment;
use App\Repositories\Interface\PaymentRepositoryInterface;
use App\Services\Payments\EsewaService;
use App\Services\Payments\KhaltiService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentService
{
    public function __construct(
        protected PaymentRepositoryInterface $paymentRepo,
        protected KhaltiService $khaltiService,
        protected EsewaService $esewaService,
    ) {}

    /**
     * Create payment from booking.
     */
    public function createFromBooking(
        Booking $booking,
        array $data
    ) {
        return DB::transaction(function () use ($booking, $data) {

            $reference = $this->generateUniqueReference();

            $payment = $this->paymentRepo->create([
                'booking_id' => $booking->id,
                'member_id' => $booking->member_id,

                'amount' => $booking->total_amount,

                'currency' => $data['currency'] ?? 'NPR',

                'payment_method' => strtoupper($data['payment_method']),

                'status' => 'PENDING',

                'transaction_id' => $reference,

                'payment_date' => now(),
            ]);

            /*
             * CASH PAYMENT
             */
            if ($payment->payment_method === 'CASH') {

                $this->completePayment($payment);

                return [
                    'payment' => $payment->fresh(),
                    'status' => 'SUCCESS',
                ];
            }

            /*
             * ONLINE PAYMENT
             */
            return match ($payment->payment_method) {

                'KHALTI' => $this->handleKhalti($payment),

                 'ESEWA' => $this->handleEsewa($payment),

                default => throw new \Exception(
                    'Unsupported payment method.'
                ),
            };
        });
    }

    public function createFromInvoice(
        Invoice $invoice,
        array $data
    ): Payment {
        $amount = round((float) ($data['amount'] ?? 0), 2);
        $extraDiscount = round(
            (float) ($data['extra_discount'] ?? 0),
            2
        );

        if ($amount <= 0 && $extraDiscount <= 0) {
            throw new \Exception(
                'Payment amount or extra discount must be greater than 0.'
            );
        }

        $remaining = round(
            (float) $invoice->total_amount
                - (float) $invoice->paid_amount
                - (float) $invoice->coupon_discount,
            2
        );

        if ($extraDiscount > $remaining) {
            throw new \Exception(
                "Extra discount ({$extraDiscount}) exceeds remaining balance ({$remaining})."
            );
        }

        if ($extraDiscount > 0) {
            $amount = $remaining - $extraDiscount;
        }

        if ($amount > $remaining) {
            throw new \Exception(
                "Payment amount ({$amount}) exceeds remaining balance ({$remaining})."
            );
        }

        return DB::transaction(function () use (
            $invoice,
            $data,
            $amount,
            $extraDiscount
        ) {

            $payment = $this->paymentRepo->create([
                'invoice_id' => $invoice->id,
                'member_id' => $invoice->member_id,
                'booking_id' => null,

                'amount' => $amount,
                'extra_discount' => $extraDiscount,

                'currency' => $data['currency'] ?? 'NPR',

                'payment_method' => strtoupper($data['payment_method']),

                // IMPORTANT
                'status' => 'PENDING',

                'transaction_id' => $data['transaction_id'] ?? null,

                'payment_date' => now(),
            ]);

            return $payment;
        });
    }

    /**
     * Handle eSewa payment.
     */
    private function handleEsewa($payment): array
    {
        $response =
            $this->esewaService->initiate($payment);

        $payment->update([
            'payment_url' => $response['payment_url'] ?? null,

            'gateway_response' => $response,
        ]);

        return [
            'payment' => $payment->fresh(),
            'gateway' => $response,
        ];
    }

    /**
     * Handle Khalti payment.
     */
    private function handleKhalti($payment): array
    {
        $response =
            $this->khaltiService->initiate($payment);

        $payment->update([
            'transaction_id' => $response['pidx'],

            'gateway_reference' => $response['pidx'],

            'payment_url' => $response['payment_url'] ?? null,

            'gateway_response' => $response,
        ]);

        return [
            'payment' => $payment->fresh(),
            'gateway' => $response,
        ];
    }

    /**
     * Complete successful payment.
     */
    public function completePayment(Payment $payment): Payment
    {
        return DB::transaction(function () use ($payment) {

            $completedStatuses = ['SUCCESS', 'COMPLETED'];

            // Prevent duplicate completion
            if (! in_array($payment->status, $completedStatuses, true)) {
                $payment->update([
                    'status' => 'COMPLETED',
                    'paid_at' => now(),
                ]);
            }

            /*
         * Invoice payment
         */
            if ($payment->invoice_id) {

                $invoice = Invoice::query()
                    ->lockForUpdate()
                    ->findOrFail($payment->invoice_id);

                // Calculate total completed payments
                $paidAmount = $invoice->payments()
                    ->whereIn('status', $completedStatuses)
                    ->sum('amount');

                $paidAmount = round((float) $paidAmount, 2);

                $payableAmount = round(
                    (float) $invoice->total_amount
                        - (float) $invoice->coupon_discount,
                    2
                );

                $remainingAmount = max(
                    0,
                    $payableAmount - $paidAmount
                );

                $status = $remainingAmount <= 0
                    ? 'paid'
                    : 'partially_paid';

                $invoice->update([
                    'paid_amount' => $paidAmount,
                    'remaining_amount' => $remainingAmount,
                    'status' => $status,
                ]);

                /*
             * Activate package only when invoice is fully paid
             */
                if ($status === 'paid') {
                    app(InvoiceService::class)
                        ->activateMemberPackage($invoice);
                }

                return $payment->fresh();
            }

            /*
         * Booking payment
         */
            if ($payment->booking_id) {

                $booking = $payment->booking;

                $booking->update([
                    'status' => 'CONFIRMED',
                    'payment_status' => 'PAID',
                    'confirmed_at' => now(),
                ]);

                return $payment->fresh();
            }

            throw new \Exception(
                'Payment must belong to either an invoice or booking.'
            );
        });
    }

    /**
     * Generate unique payment reference.
     */
    private function generateUniqueReference(): string
    {
        do {
            $reference =
                'PAY-'.
                strtoupper(Str::random(8));
        } while (
            $this->paymentRepo
                ->existsByReference($reference)
        );

        return $reference;
    }
}
