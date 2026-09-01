<?php

namespace App\Services\Payments;

use App\Models\Payment;
use Illuminate\Support\Facades\Http;

class EsewaService
{
    protected string $baseUrl;
    protected string $productCode;
    protected string $secret;

    public function __construct()
    {
        $this->baseUrl = config('services.esewa.base_url');

        $this->productCode = config('services.esewa.product_code');

        $this->secret = config('services.esewa.secret');
    }

    public function initiate(Payment $payment): array
    {
        $totalAmount = number_format(
            (float) $payment->amount,
            2,
            '.',
            ''
        );

        $signature = $this->generateSignature(
            $totalAmount,
            $payment->transaction_id
        );

        return [
            'payment_url' => "{$this->baseUrl}/main/v2/form",

            'params' => [
                'amount' => $totalAmount,
                'tax_amount' => '0',
                'total_amount' => $totalAmount,
                'product_service_charge' => '0',
                'product_delivery_charge' => '0',

                'transaction_uuid' => $payment->transaction_id,
                'product_code' => $this->productCode,

                'success_url' => route('payments.esewa.success'),
                'failure_url' => route('payments.esewa.failure'),

                'signed_field_names' =>
                    'total_amount,transaction_uuid,product_code',

                'signature' => $signature,
            ],
        ];
    }

    private function generateSignature(
        string $totalAmount,
        string $transactionUuid
    ): string {
        $message =
            "total_amount={$totalAmount}," .
            "transaction_uuid={$transactionUuid}," .
            "product_code={$this->productCode}";

        return base64_encode(
            hash_hmac(
                'sha256',
                $message,
                $this->secret,
                true
            )
        );
    }

    public function verify(array $data): array
    {
        $encoded = $data['data'] ?? null;

        if (!$encoded) {
            throw new \Exception(
                'Missing eSewa data payload.'
            );
        }

        $decoded = json_decode(
            base64_decode($encoded),
            true
        );

        if (!$decoded) {
            throw new \Exception(
                'Invalid eSewa response.'
            );
        }

        $transactionUuid =
            $decoded['transaction_uuid'] ?? null;

        if (!$transactionUuid) {
            throw new \Exception(
                'Transaction UUID missing.'
            );
        }

        $payment = Payment::where(
            'transaction_id',
            $transactionUuid
        )->first();

        if (!$payment) {
            throw new \Exception(
                'Payment not found.'
            );
        }

        // Verify amount
        if (
            (float) $payment->amount !==
            (float) $decoded['total_amount']
        ) {
            throw new \Exception(
                'Payment amount mismatch.'
            );
        }

        // Already processed
        if ($payment->status === 'SUCCESS') {
            return [
                'status' => 'Completed',
                'transaction_id' =>
                    $payment->transaction_id,
            ];
        }

        // Verify transaction with eSewa
        $response = Http::get(
            "{$this->baseUrl}/transaction/status/",
            [
                'product_code' => $this->productCode,
                'total_amount' =>
                    $decoded['total_amount'],
                'transaction_uuid' =>
                    $transactionUuid,
            ]
        );

        if ($response->failed()) {
            throw new \Exception(
                'eSewa verification failed.'
            );
        }

        $verify = $response->json();

        if (
            ($verify['status'] ?? '') !== 'COMPLETE'
        ) {
            $payment->update([
                'status' => 'FAILED',
            ]);

            throw new \Exception(
                'Payment not completed.'
            );
        }

        // Payment successful
        $payment->update([
            'status' => 'SUCCESS',
            'paid_at' => now(),
            'gateway_reference' =>
                $verify['transaction_code'] ?? null,
        ]);

        // Confirm booking
        $payment->booking?->update([
            'status' => 'CONFIRMED',
            'payment_status' => 'PAID',
        ]);

        return [
            'status' => 'Completed',
            'transaction_id' =>
                $payment->transaction_id,
        ];
    }
}
