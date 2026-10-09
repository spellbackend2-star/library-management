<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Models\SubscriptionPayment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
     * Initiate Khalti payment for tenant invoice.
     */
    public function initiate(Payment $payment): array
    {

        $invoice = $payment->invoice;

        if (! $invoice) {
            throw new \Exception(
                'Invoice not found for this payment.'
            );
        }

        if (! $invoice->invoice_number) {
            throw new \Exception(
                'Invoice number not found.'
            );
        }

        $maxPayable = round(
            (float) $invoice->total_amount,
            2
        );

        $paidAmount = round(
            (float) $invoice->paid_amount,
            2
        );

        $remaining = max(
            0,
            $maxPayable - $paidAmount
        );

        if ((float) $payment->amount > $remaining) {
            throw new \Exception(
                "Payment amount ({$payment->amount}) exceeds remaining balance ({$remaining})."
            );
        }

        /*
     * IMPORTANT:
     * Khalti must return to BACKEND first.
     */
        $callbackBaseUrl = rtrim((string) config('services.khalti.callback_base_url'), '/');
        $callbackPath = route(
            'payments.khalti.verify',
            ['paymentId' => $payment->id],
            false
        );
        $returnUrl = $callbackBaseUrl . $callbackPath;


        $response = Http::withHeaders([
            'Authorization' => 'Key ' . $this->secretKey,
            'Content-Type' => 'application/json',
        ])->post(
            rtrim($this->baseUrl, '/')
                . '/epayment/initiate/',
            [
                'return_url' => $returnUrl,

                'website_url' => config('app.url'),

                'amount' => (int) round(
                    $payment->amount * 100
                ),

                'purchase_order_id' =>
                $invoice->invoice_number,

                'purchase_order_name' =>
                'Invoice #' . $invoice->invoice_number,
            ]
        );

        if ($response->failed()) {
            throw new \Exception(
                'Khalti initiation failed: '
                    . $response->body()
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
            'gateway_reference' => $result['pidx'],
            'payment_url' => $result['payment_url'] ?? null,
            'gateway_response' => $result,
        ]);

        return $result;
    }

    /**
     * Initiate Khalti payment for central subscription.
     */
    /**
     * Initiate Khalti payment for central subscription.
     */
    public function initiateSubscription(
        SubscriptionPayment $payment,
        ?string $returnUrl = null
    ): array {
        $subscription = $payment->subscription;

        if (! $subscription) {
            throw new \Exception('Subscription not found for this payment.');
        }

        $plan = $subscription->plan;

        if (! $plan) {
            throw new \Exception('Subscription plan not found.');
        }

        /*
     * Khalti must return to BACKEND first.
     *
     * After backend verification, the backend will redirect
     * the user to the frontend return_url.
     */
        $verifyUrl = route(
            'central.subscription-payments.verify-khalti',
            [
                'payment' => $payment->id,
                'return_url' => $returnUrl,
            ]
        );

        $response = Http::withHeaders([
            'Authorization' => 'Key ' . $this->secretKey,
            'Content-Type' => 'application/json',
        ])->post(
            rtrim($this->baseUrl, '/') . '/epayment/initiate/',
            [
                'return_url' => $verifyUrl,

                'website_url' => config('app.url'),

                'amount' => (int) round(
                    $payment->amount * 100
                ),

                'purchase_order_id' => 'SUB-' . $payment->id,

                'purchase_order_name' => 'Subscription #' . $payment->id,
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
            'gateway_response' => $result,
        ]);

        return $result;
    }

    /**
     * Verify Khalti payment.
     */
    public function verify(string $pidx): array
    {


        $response = Http::withHeaders([
            'Authorization' => 'Key ' . $this->secretKey,
            'Content-Type' => 'application/json',
        ])->post(
            rtrim($this->baseUrl, '/') . '/epayment/lookup/',
            [
                'pidx' => $pidx,
            ]
        );

        if ($response->failed()) {
            throw new \Exception(
                'Khalti verification failed: ' .
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

    /**
     * Verify a central subscription payment and ensure the Khalti lookup
     * belongs to this payment and amount. Khalti amounts are in paisa;
     * this application stores payment amounts in NPR.
     */
    public function verifySubscriptionPayment(SubscriptionPayment $payment): array
    {
        $gatewayResponse = $payment->gateway_response ?? [];
        $pidx = is_array($gatewayResponse)
            ? ($gatewayResponse['pidx'] ?? $payment->transaction_id)
            : $payment->transaction_id;

        if (! is_string($pidx) || trim($pidx) === '') {
            throw new \RuntimeException('Khalti pidx is missing for this payment.');
        }

        $result = $this->verify($pidx);
        $lookup = $result['gateway_response'] ?? [];
        $returnedPidx = $lookup['pidx'] ?? null;

        if (! is_string($returnedPidx) || ! hash_equals($pidx, $returnedPidx)) {
            throw new \RuntimeException('Khalti returned a different or missing pidx for this payment.');
        }

        $expectedPaisa = (int) round((float) $payment->amount * 100);
        $receivedPaisa = $lookup['total_amount'] ?? null;

        if (! is_numeric($receivedPaisa) || (int) $receivedPaisa !== $expectedPaisa) {
            throw new \RuntimeException('Khalti payment amount does not match the invoice payment amount.');
        }

        $returnedOrderId = $lookup['purchase_order_id'] ?? null;
        $expectedOrderId = 'SUB-' . $payment->id;

        if (
            $returnedOrderId !== null
            && (! is_string($returnedOrderId) || ! hash_equals($expectedOrderId, $returnedOrderId))
        ) {
            throw new \RuntimeException('Khalti returned a different purchase order for this payment.');
        }

        return $result;
    }
}
