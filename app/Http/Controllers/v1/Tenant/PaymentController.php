<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\PaymentIndexRequest;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Services\Payments\EsewaService;
use App\Services\Payments\KhaltiService;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Log;
use Throwable;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService,
        protected KhaltiService $khaltiService,
        protected EsewaService $esewaService,
    ) {}

    private function successResponse($data, string $message, int $status = 200, ?array $meta = null)
    {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];

        if ($meta !== null) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $status);
    }

    private function errorResponse(string $message, int $status = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }

    public function index(PaymentIndexRequest $request)
    {
        $result = $this->paymentService->getAll(
            $request->validated()
        );

        return $this->successResponse(
            PaymentResource::collection($result['data']),
            'Payments retrieved successfully',
            200,
            $result['meta']
        );
    }

    public function show(int $id)
    {
        $payment = $this->paymentService->findById($id);

        if (! $payment) {
            return $this->errorResponse(
                'Payment not found',
                404
            );
        }

        return $this->successResponse(
            new PaymentResource($payment),
            'Payment retrieved successfully'
        );
    }

    public function verifyKhalti(int $paymentId)
    {
        $payment = Payment::with('invoice')->findOrFail($paymentId);

        if ($payment->payment_method !== 'KHALTI') {
            return $this->errorResponse(
                'Invalid payment method for Khalti.',
                400
            );
        }

        if (! $payment->transaction_id) {
            return $this->errorResponse(
                'Khalti transaction ID not found.',
                400
            );
        }

        return $this->verifyGatewayPayment(
            $payment,
            fn() => $this->khaltiService->verify(
                $payment->transaction_id
            ),
            'Khalti'
        );
    }

    public function verifyEsewa(int $paymentId)
    {
        $payment = Payment::with('invoice')->findOrFail($paymentId);

        if ($payment->payment_method !== 'ESEWA') {
            return $this->errorResponse(
                'Invalid payment method for eSewa.',
                400
            );
        }

        return $this->verifyGatewayPayment(
            $payment,
            fn() => $this->esewaService->verify($payment),
            'eSewa'
        );
    }
    private function frontendUrl(): string
    {
        return rtrim(
            config('app.frontend_url'),
            '/'
        );
    }
    private function verifyGatewayPayment(
        Payment $payment,
        callable $verification,
        string $gateway
    ) {
        try {
            /*
         * Prevent duplicate completion.
         */
            if ($payment->status === 'SUCCESS') {

                if ($gateway === 'Khalti') {
                    return redirect()->away(
                        $this->frontendUrl()
                            . '/admin/invoices/'
                            . $payment->invoice_id
                            . '?payment=success'
                            . '&payment_id=' . $payment->id
                            . '&invoice_id=' . $payment->invoice_id
                            . '&amount=' . $payment->amount
                            . '&message=' . urlencode('Payment completed successfully.')
                    );
                }

                return $this->successResponse(
                    [
                        'payment' => new PaymentResource($payment->fresh()),
                        'invoice' => new InvoiceResource(
                            $payment->invoice->fresh()
                        ),
                    ],
                    "{$gateway} payment already completed successfully."
                );
            }

            /*
         * Ask the payment gateway for the real status.
         */
            $result = $verification();

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
                    'gateway_response' =>
                    $result['gateway_response'] ?? null,

                    'transaction_id' =>
                    $result['transaction_id']
                        ?? $payment->transaction_id,
                ]);

                Log::info('KHALTI PAYMENT COMPLETING', [
                    'payment_id' => $payment->id,
                    'invoice_id' => $payment->invoice_id,
                    'amount' => $payment->amount,
                    'gateway_status' => $gatewayStatus,
                ]);

                /*
             * Complete payment.
             *
             * This updates:
             * Payment:
             *   PENDING -> SUCCESS
             *
             * Invoice:
             *   paid_amount
             *   remaining_amount
             *   status
             */
                $completedPayment = $this->paymentService
                    ->completePayment($payment);

                /*
             * Refresh relationships/data after completion.
             */
                $completedPayment->refresh();
                $completedPayment->load('invoice');

                $invoice = $completedPayment->invoice;

                Log::info('KHALTI PAYMENT COMPLETED', [
                    'payment_id' => $completedPayment->id,
                    'invoice_id' => $completedPayment->invoice_id,
                    'amount' => $completedPayment->amount,
                    'status' => $completedPayment->status,
                    'invoice_status' => $invoice?->status,
                    'paid_amount' => $invoice?->paid_amount,
                    'remaining_amount' => $invoice?->remaining_amount,
                ]);

                /*
             * Khalti:
             * Backend verifies and saves first,
             * then redirects to frontend.
             */
                if ($gateway === 'Khalti') {

                    $frontendUrl =
                        $this->frontendUrl()
                        . '/admin/invoices/'
                        . $completedPayment->invoice_id;

                    $query = http_build_query([
                        'payment' => 'success',
                        'payment_id' => $completedPayment->id,
                        'invoice_id' => $completedPayment->invoice_id,
                        'amount' => $completedPayment->amount,
                        'status' => $completedPayment->status,
                        'message' => 'Payment completed successfully.',
                    ]);

                    $redirectUrl = $frontendUrl . '?' . $query;

                    Log::info('KHALTI FRONTEND REDIRECT', [
                        'payment_id' => $completedPayment->id,
                        'invoice_id' => $completedPayment->invoice_id,
                        'redirect_url' => $redirectUrl,
                    ]);

                    return redirect()->away($redirectUrl);
                }

                /*
             * eSewa JSON response.
             */
                return $this->successResponse([
                    'payment' => new PaymentResource(
                        $completedPayment
                    ),

                    'invoice' => new InvoiceResource(
                        $invoice
                    ),
                ], "{$gateway} payment completed successfully.");
            }

            /*
         * Payment is still pending.
         */
            if ($gatewayStatus === 'PENDING') {

                $payment->update([
                    'status' => 'PENDING',

                    'gateway_response' =>
                    $result['gateway_response'] ?? null,
                ]);

                if ($gateway === 'Khalti') {

                    $redirectUrl =
                        $this->frontendUrl()
                        . '/admin/invoices/'
                        . $payment->invoice_id
                        . '?payment=pending'
                        . '&payment_id=' . $payment->id
                        . '&invoice_id=' . $payment->invoice_id
                        . '&message='
                        . urlencode('Payment is still pending.');

                    Log::info('KHALTI FRONTEND REDIRECT - PENDING', [
                        'payment_id' => $payment->id,
                        'invoice_id' => $payment->invoice_id,
                        'redirect_url' => $redirectUrl,
                    ]);

                    return redirect()->away($redirectUrl);
                }

                return $this->successResponse(
                    new PaymentResource($payment->fresh()),
                    "{$gateway} payment is still pending."
                );
            }

            /*
         * Payment failed/cancelled.
         */
            $payment->update([
                'status' => 'FAILED',

                'gateway_response' =>
                $result['gateway_response'] ?? null,
            ]);

            /*
         * Khalti:
         * Redirect back to frontend.
         */
            if ($gateway === 'Khalti') {

                $redirectUrl =
                    $this->frontendUrl()
                    . '/admin/invoices/'
                    . $payment->invoice_id
                    . '?payment=failed'
                    . '&payment_id=' . $payment->id
                    . '&invoice_id=' . $payment->invoice_id
                    . '&message='
                    . urlencode('Payment failed or was cancelled.');

                Log::warning('KHALTI FRONTEND REDIRECT - FAILED', [
                    'payment_id' => $payment->id,
                    'invoice_id' => $payment->invoice_id,
                    'redirect_url' => $redirectUrl,
                    'gateway_status' => $gatewayStatus,
                ]);

                return redirect()->away($redirectUrl);
            }

            return $this->errorResponse(
                "{$gateway} payment failed or was cancelled.",
                400
            );
        } catch (Throwable $e) {

            Log::error('KHALTI PAYMENT VERIFICATION ERROR', [
                'payment_id' => $payment->id,
                'invoice_id' => $payment->invoice_id,
                'gateway' => $gateway,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            /*
         * Send Khalti user back to frontend
         * if backend verification encounters an error.
         */
            if ($gateway === 'Khalti') {

                $redirectUrl =
                    $this->frontendUrl()
                    . '/admin/invoices/'
                    . $payment->invoice_id
                    . '?payment=error'
                    . '&payment_id=' . $payment->id
                    . '&invoice_id=' . $payment->invoice_id
                    . '&message='
                    . urlencode('Unable to verify payment.');

                Log::error('KHALTI FRONTEND REDIRECT - ERROR', [
                    'payment_id' => $payment->id,
                    'invoice_id' => $payment->invoice_id,
                    'redirect_url' => $redirectUrl,
                ]);

                return redirect()->away($redirectUrl);
            }

            return $this->errorResponse(
                $e->getMessage(),
                500
            );
        }
    }
}
