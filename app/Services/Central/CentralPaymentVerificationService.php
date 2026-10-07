<?php

namespace App\Services\Central;

use App\Models\SubscriptionPayment;
use App\Services\Payments\EsewaService;
use App\Services\Payments\KhaltiService;
use App\Traits\CentralPaymentRedirectTrait;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class CentralPaymentVerificationService
{
    use CentralPaymentRedirectTrait;

    public function __construct(
        protected CentralPaymentService $centralPaymentService,
        protected KhaltiService $khaltiService,
        protected EsewaService $esewaService
    ) {}

    /**
     * Verify Khalti payment callback and redirect to frontend.
     */
    public function verifyKhalti(
        SubscriptionPayment $payment,
        ?string $returnUrl
    ): RedirectResponse {
        try {
            /*
             * Prevent duplicate completion.
             */
            if (in_array(
                $payment->status,
                ['SUCCESS', 'COMPLETED'],
                true
            )) {
                return $this->redirectKhaltiFrontend(
                    $payment,
                    'success',
                    'Payment already completed successfully.',
                    $returnUrl
                );
            }

            /*
             * Transaction ID is required.
             */
            if (! $payment->transaction_id) {
                throw new Exception(
                    'Khalti transaction ID not found.'
                );
            }

            /*
             * Ask Khalti for actual payment status.
             */
            $result = $this->khaltiService->verify(
                $payment->transaction_id
            );

            $gatewayStatus = strtoupper(
                (string) ($result['status'] ?? '')
            );

            /*
             * Payment successful.
             */
            if (in_array(
                $gatewayStatus,
                ['COMPLETED', 'SUCCESS'],
                true
            )) {
                $payment->update([
                    'gateway_response' => $result['gateway_response'] ?? null,
                    'transaction_id' => $result['transaction_id'] ?? $payment->transaction_id,
                ]);

                Log::info(
                    'CENTRAL KHALTI PAYMENT COMPLETING',
                    [
                        'payment_id' => $payment->id,
                        'subscription_id' => $payment->subscription_id,
                        'invoice_id' => $payment->invoice_id,
                        'amount' => $payment->amount,
                        'gateway_status' => $gatewayStatus,
                    ]
                );

                /*
                 * Apply this installment to the invoice and activate the
                 * subscription after any successful payment.
                 */
                $this->centralPaymentService->completeCentralCashPayment($payment);

                /*
                 * Refresh after completion.
                 */
                $payment->refresh();
                $payment->load([
                    'subscription.plan',
                    'tenant.domains',
                    'invoice',
                ]);

                Log::info(
                    'CENTRAL KHALTI PAYMENT COMPLETED',
                    [
                        'payment_id' => $payment->id,
                        'subscription_id' => $payment->subscription_id,
                        'invoice_id' => $payment->invoice_id,
                        'amount' => $payment->amount,
                        'status' => $payment->status,
                        'invoice_status' => $payment->invoice?->status,
                        'paid_amount' => $payment->invoice?->paid_amount,
                        'remaining_amount' => $payment->invoice?->remaining_amount,
                        'subscription_status' => $payment->subscription?->status,
                        'tenant_status' => $payment->tenant?->status,
                    ]
                );

                return $this->redirectKhaltiFrontend(
                    $payment,
                    'success',
                    'Payment completed successfully.',
                    $returnUrl
                );
            }

            /*
             * Payment still pending.
             */
            if ($gatewayStatus === 'PENDING') {
                $payment->update([
                    'status' => 'PENDING',
                    'gateway_response' => $result['gateway_response'] ?? null,
                ]);

                $payment->refresh();

                Log::info(
                    'CENTRAL KHALTI PAYMENT PENDING',
                    [
                        'payment_id' => $payment->id,
                        'invoice_id' => $payment->invoice_id,
                    ]
                );

                return $this->redirectKhaltiFrontend(
                    $payment,
                    'pending',
                    'Payment is still pending.',
                    $returnUrl
                );
            }

            /*
             * Payment failed/cancelled.
             */
            $payment->update([
                'status' => 'FAILED',
                'gateway_response' => $result['gateway_response'] ?? null,
            ]);

            $payment->refresh();

            Log::warning(
                'CENTRAL KHALTI PAYMENT FAILED',
                [
                    'payment_id' => $payment->id,
                    'invoice_id' => $payment->invoice_id,
                    'gateway_status' => $gatewayStatus,
                ]
            );

            return $this->redirectKhaltiFrontend(
                $payment,
                'failed',
                'Payment failed or was cancelled.',
                $returnUrl
            );
        } catch (Throwable $e) {
            Log::error(
                'CENTRAL KHALTI PAYMENT VERIFICATION ERROR',
                [
                    'payment_id' => $payment->id,
                    'subscription_id' => $payment->subscription_id,
                    'invoice_id' => $payment->invoice_id,
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );

            return $this->redirectKhaltiFrontend(
                $payment,
                'error',
                'Unable to verify payment.',
                $returnUrl
            );
        }
    }

    /**
     * Verify eSewa payment callback and redirect to frontend.
     */
    public function verifyEsewa(
        SubscriptionPayment $payment,
        array $payload,
        ?string $returnUrl
    ): RedirectResponse {
        try {
            /*
             * Prevent duplicate completion.
             */
            if (in_array(
                $payment->status,
                ['SUCCESS', 'COMPLETED'],
                true
            )) {
                return $this->redirectEsewaFrontend(
                    $payment,
                    'success',
                    'Payment already completed successfully.',
                    $returnUrl
                );
            }

            /*
             * Verify eSewa payment.
             */
            $result = $this->esewaService->verify($payload);

            $gatewayStatus = strtoupper(
                (string) ($result['status'] ?? '')
            );

            /*
             * Payment successful.
             */
            if (in_array(
                $gatewayStatus,
                ['COMPLETED', 'SUCCESS'],
                true
            )) {
                $payment->update([
                    'gateway_response' => $result['gateway_response'] ?? null,
                    'transaction_id' => $result['transaction_id'] ?? $payment->transaction_id,
                ]);

                Log::info(
                    'CENTRAL ESEWA PAYMENT COMPLETING',
                    [
                        'payment_id' => $payment->id,
                        'subscription_id' => $payment->subscription_id,
                        'invoice_id' => $payment->invoice_id,
                        'amount' => $payment->amount,
                        'gateway_status' => $gatewayStatus,
                    ]
                );

                /*
                 * Complete payment.
                 */
                $this->centralPaymentService->completeCentralCashPayment($payment);

                $payment->refresh();
                $payment->load([
                    'subscription.plan',
                    'tenant.domains',
                    'invoice',
                ]);

                return $this->redirectEsewaFrontend(
                    $payment,
                    'success',
                    'Payment completed successfully.',
                    $returnUrl
                );
            }

            /*
             * Pending.
             */
            if ($gatewayStatus === 'PENDING') {
                $payment->update([
                    'status' => 'PENDING',
                    'gateway_response' => $result['gateway_response'] ?? null,
                ]);

                $payment->refresh();

                return $this->redirectEsewaFrontend(
                    $payment,
                    'pending',
                    'Payment is still pending.',
                    $returnUrl
                );
            }

            /*
             * Failed/cancelled.
             */
            $payment->update([
                'status' => 'FAILED',
                'gateway_response' => $result['gateway_response'] ?? null,
            ]);

            $payment->refresh();

            return $this->redirectEsewaFrontend(
                $payment,
                'failed',
                'Payment failed or was cancelled.',
                $returnUrl
            );
        } catch (Throwable $e) {
            Log::error(
                'CENTRAL ESEWA PAYMENT VERIFICATION ERROR',
                [
                    'payment_id' => $payment->id,
                    'subscription_id' => $payment->subscription_id,
                    'invoice_id' => $payment->invoice_id,
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );

            return $this->redirectEsewaFrontend(
                $payment,
                'error',
                'Unable to verify payment.',
                $returnUrl
            );
        }
    }
}
