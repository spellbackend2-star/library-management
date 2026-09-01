<?php

namespace App\Services;

use App\Models\Booking;
use App\Repositories\Interface\PaymentRepositoryInterface;
use App\Services\Payments\KhaltiService;
use App\Services\Payments\EsewaService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

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
                'member_id' => $booking->member_id,
                'booking_id' => $booking->id,

                'amount' => $booking->total_amount,

                'currency' =>
                    $data['currency'] ?? 'NPR',

                'payment_method' =>
                    strtoupper($data['payment_method']),

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

                'KHALTI' =>
                    $this->handleKhalti($payment),

                'ESEWA' =>
                    $this->handleEsewa($payment),

                default =>
                    throw new \Exception(
                        'Unsupported payment method.'
                    ),
            };
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
            'payment_url' =>
                $response['payment_url'] ?? null,

            'gateway_response' =>
                $response,
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
            'transaction_id' =>
                $response['pidx'] ?? $payment->transaction_id,

            'gateway_reference' =>
                $response['pidx'] ?? null,

            'payment_url' =>
                $response['payment_url'] ?? null,

            'gateway_response' =>
                $response,
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

        if (!$booking) {
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
                'PAY-' .
                strtoupper(Str::random(8));

        } while (
            $this->paymentRepo
                ->existsByReference($reference)
        );

        return $reference;
    }
}
