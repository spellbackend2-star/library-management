<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Invoice;
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
     * Get all payments.
     */
    public function getAll(array $filters = [])
    {
        return $this->paymentRepo->getAll($filters);
    }

    /**
     * Find payment.
     */
    public function findById(int $id)
    {
        return $this->paymentRepo->findById($id);
    }

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

    /**
     * Create payment against an invoice.
     */
    public function createFromInvoice(
        Invoice $invoice,
        array $data
    ): Payment {
        $amount = round((float) ($data['amount'] ?? 0), 2);
        $extraDiscount = round((float) ($data['extra_discount'] ?? 0), 2);

        if ($amount <= 0 && $extraDiscount <= 0) {
            throw new \Exception('Payment amount or extra discount must be greater than 0.');
        }

        $remaining = round((float) $invoice->total_amount - (float) $invoice->paid_amount - (float) $invoice->coupon_discount, 2);

        if ($extraDiscount > $remaining) {
            throw new \Exception("Extra discount ({$extraDiscount}) exceeds remaining balance ({$remaining}).");
        }

        if ($extraDiscount > 0) {
            $amount = $remaining - $extraDiscount;
        }

        if ($amount > $remaining) {
            throw new \Exception("Payment amount ({$amount}) exceeds remaining balance ({$remaining}).");
        }

        return DB::transaction(function () use ($invoice, $data, $amount, $extraDiscount) {
            $payment = $this->paymentRepo->create([
                'invoice_id' => $invoice->id,
                'member_id' => $invoice->member_id,
                'booking_id' => null,
                'amount' => $amount,
                'extra_discount' => $extraDiscount,
                'currency' => $data['currency'] ?? 'NPR',

                'payment_method' => strtoupper($data['payment_method']),

                'status' => 'SUCCESS',

                'transaction_id' => $data['transaction_id'] ?? null,

                'payment_date' => $data['paid_at'] ?? now(),

                'paid_at' => $data['paid_at'] ?? now(),
            ]);

            $newPaidAmount = round(
                (float) $invoice->paid_amount + (float) $payment->amount,
                2
            );

            $newStatus = 'partially_paid';

            if ($newPaidAmount >= (float) $invoice->total_amount - (float) $invoice->coupon_discount) {
                $newStatus = 'paid';
            }

            $invoiceService = app(InvoiceService::class);
            $updatedInvoice = $invoiceService->findById($invoice->id);

            if ($updatedInvoice) {
                $updatedInvoice->update([
                    'paid_amount' => $newPaidAmount,
                    'status' => $newStatus,
                ]);
            }

            if ($newStatus === 'paid') {
                $invoiceService->activateMemberPackage($invoice);
            }

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
            'transaction_id' => $response['pidx'] ?? $payment->transaction_id,

            'gateway_reference' => $response['pidx'] ?? null,

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
    public function completePayment($payment)
    {
        $payment->update([
            'status' => 'SUCCESS',
            'paid_at' => now(),
        ]);

        $booking = $payment->booking;

        if (! $booking) {
            throw new \Exception(
                'Booking not found for payment.'
            );
        }

        $booking->update([
            'status' => 'CONFIRMED',
            'payment_status' => 'PAID',
            'confirmed_at' => now(),
        ]);

        return $payment->fresh();
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
