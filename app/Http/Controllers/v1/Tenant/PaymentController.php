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
use Throwable;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService,
        protected KhaltiService $khaltiService,
        protected EsewaService $esewaService,
    ) {}

    private function successResponse($data, string $message, int $status = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
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
        $payments = $this->paymentService->getAll(
            $request->validated()
        );

        return $this->successResponse(
            PaymentResource::collection($payments),
            'Payments retrieved successfully'
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
            fn () => $this->khaltiService->verify(
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
            fn () => $this->esewaService->verify($payment),
            'eSewa'
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
                return redirect()->away(
                    config('app.frontend_url') .
                    'admin/invoices/' .
                    $payment->payment_id .
                    '?payment=success'
                );
            }

            /*
             * Ask the payment gateway for the real status.
             */
            $result = $verification();

            /*
             * Payment successful.
             */
            if (in_array(
                strtoupper($result['status'] ?? ''),
                ['COMPLETED', 'SUCCESS'],
                true
            )) {
                $payment->update([
                    'gateway_response' => $result['gateway_response'] ?? null,
                    'transaction_id' => $result['transaction_id']
                        ?? $payment->transaction_id,
                ]);

                /*
                 * This should update:
                 * - payment status
                 * - invoice paid amount
                 * - invoice remaining amount
                 * - invoice status
                 */
                $completedPayment = $this->paymentService
                    ->completePayment($payment);

                /*
                 * Khalti:
                 * Backend verifies first, then redirects
                 * the customer to frontend.
                 */
                if ($gateway === 'Khalti') {
                    return redirect()->away(
                        config('app.frontend_url') .
                        '/invoices/' .
                        $completedPayment->invoice_id .
                        '?payment=success'
                    );
                }

                /*
                 * eSewa can continue with JSON response.
                 */
                return $this->successResponse([
                    'payment' => new PaymentResource(
                        $completedPayment->fresh()
                    ),
                    'invoice' => new InvoiceResource(
                        $completedPayment->invoice->fresh()
                    ),
                ], "{$gateway} payment completed successfully.");
            }

            /*
             * Payment is still pending.
             */
            if (strtoupper($result['status'] ?? '') === 'PENDING') {
                $payment->update([
                    'status' => 'PENDING',
                    'gateway_response' => $result['gateway_response'] ?? null,
                ]);

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
                'gateway_response' => $result['gateway_response'] ?? null,
            ]);

            /*
             * For Khalti, send the user back to frontend
             * with failed status.
             */
            if ($gateway === 'Khalti') {
                return redirect()->away(
                    config('app.frontend_url') .
                    '/invoices/' .
                    $payment->payment_id .
                    '?payment=failed'
                );
            }

            return $this->errorResponse(
                "{$gateway} payment failed or was cancelled.",
                400
            );

        } catch (Throwable $e) {
            return $this->errorResponse(
                $e->getMessage(),
                500
            );
        }
    }
}
