<?php

namespace App\Services\Payments;

use App\Models\Payment;
use Illuminate\Support\Facades\Http;

class KhaltiService
{
    protected string $baseUrl;
    protected string $secretKey;

    public function __construct()
    {
        $this->baseUrl = config('services.khalti.base_url');

        $this->secretKey = config(
            'services.khalti.secret_key'
        );
    }

    /**
     * Initiate Khalti payment.
     */
    public function initiate(Payment $payment): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Key ' . $this->secretKey,
            'Content-Type' => 'application/json',
        ])->post(
            rtrim($this->baseUrl, '/') . '/epayment/initiate/',
            [
                'return_url' => route(
                    'payments.khalti.verify',
                    [
                        'payment' => $payment->id,
                    ]
                ),

                'website_url' => config('app.url'),

                // Khalti amount is in paisa
                'amount' => (int) round(
                    $payment->amount * 100
                ),

                'purchase_order_id' =>
                    $payment->booking->booking_reference,

                'purchase_order_name' =>
                    'Booking #' . $payment->booking_id,
            ]
        );

        if ($response->failed()) {
            throw new \Exception(
                'Khalti initiation failed: ' .
                $response->body()
            );
        }

        $result = $response->json();

        if (empty($result['pidx'])) {
            throw new \Exception(
                'Khalti did not return a payment ID.'
            );
        }

        $payment->update([
            'transaction_id' => $result['pidx'],
        ]);

        return $result;
    }

    /**
     * Verify Khalti payment.
     */
    public function verify(Payment $payment): array
    {
        // Already successful
        if ($payment->status === 'SUCCESS') {
            return [
                'status' => 'Completed',
                'transaction_id' =>
                    $payment->transaction_id,
                'payment' => $payment,
            ];
        }

        if (!$payment->transaction_id) {
            throw new \Exception(
                'Khalti pidx not found.'
            );
        }

        $response = Http::withHeaders([
            'Authorization' =>
                'Key ' . $this->secretKey,
            'Content-Type' => 'application/json',
        ])->post(
            rtrim($this->baseUrl, '/') .
            '/epayment/lookup/',
            [
                'pidx' => $payment->transaction_id,
            ]
        );

        if ($response->failed()) {
            throw new \Exception(
                'Khalti verification failed: ' .
                $response->body()
            );
        }

        $result = $response->json();

        $status = $result['status'] ?? 'Unknown';

        /*
         * Payment completed
         */
        if ($status === 'Completed') {

            $payment->update([
                'status' => 'SUCCESS',
                'paid_at' => now(),
                'gateway_response' => $result,
            ]);

            $payment->booking?->update([
                'status' => 'CONFIRMED',
                'payment_status' => 'PAID',
                'confirmed_at' => now(),
            ]);
        }

        /*
         * Payment still pending
         */
        elseif ($status === 'Pending') {

            $payment->update([
                'status' => 'PENDING',
                'gateway_response' => $result,
            ]);
        }

        /*
         * Payment failed/canceled/expired
         */
        else {

            $payment->update([
                'status' => 'FAILED',
                'gateway_response' => $result,
            ]);
        }

        return [
            'status' => $status,

            'transaction_id' =>
                $result['transaction_id']
                ?? $payment->transaction_id,

            'payment' => $payment->fresh(),
        ];
    }
}
