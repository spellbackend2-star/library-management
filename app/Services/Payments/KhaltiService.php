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
        $this->secretKey = config('services.khalti.secret_key');
    }

    /**
     * Initiate Khalti payment.
     */
    public function initiate(Payment $payment): array
    {
        $invoice = $payment->invoice;

        if (! $invoice) {
            throw new \Exception('Invoice not found for this payment.');
        }

        if (! $invoice->invoice_number) {
            throw new \Exception('Invoice number not found.');
        }

        // Verify remaining balance before initiating
        $maxPayable = round((float) $invoice->total_amount - (float) $invoice->coupon_discount, 2);
        $paidAmount = round((float) $invoice->paid_amount, 2);
        $remaining = max(0, $maxPayable - $paidAmount);

        if ($payment->amount > $remaining) {
            throw new \Exception("Payment amount ({$payment->amount}) exceeds remaining balance ({$remaining}). Max payable: {$maxPayable}, Already paid: {$paidAmount}");
        }

        $response = Http::withHeaders([
            'Authorization' => 'Key '.$this->secretKey,
            'Content-Type' => 'application/json',
        ])->post(
            rtrim($this->baseUrl, '/').'/epayment/initiate/',
            [
                'return_url' => route(
                    'payments.khalti.verify',
                    [
                        'paymentId' => $payment->id,
                    ]
                ),

                'website_url' => config('app.url'),

                'amount' => (int) round(
                    $payment->amount * 100
                ),

                'purchase_order_id' => $invoice->invoice_number,

                'purchase_order_name' => 'Invoice #'.$invoice->invoice_number,
            ]
        );

        if ($response->failed()) {
            throw new \Exception(
                'Khalti initiation failed: '.
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
    public function verify(string $pidx): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Key '.$this->secretKey,
            'Content-Type' => 'application/json',
        ])->post(
            rtrim($this->baseUrl, '/').'/epayment/lookup/',
            [
                'pidx' => $pidx,
            ]
        );

        if ($response->failed()) {
            throw new \Exception(
                'Khalti verification failed: '.
                    $response->body()
            );
        }

        $result = $response->json();

        return [
            'status' => $result['status'] ?? 'Unknown',

            'transaction_id' => $result['transaction_id']
                    ?? $pidx,

            'gateway_response' => $result,
        ];
    }
}
