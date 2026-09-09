<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\PaymentService;
use App\Services\Payments\EsewaService;
use App\Services\Payments\KhaltiService;
use Illuminate\Http\Request;
use Throwable;

class PaymentGatewayController extends Controller
{
    public function __construct(
        private PaymentService $paymentService,
        private KhaltiService $khaltiService,
        private EsewaService $esewaService,
    ) {}

    public function initiate(int $id)
    {
        $payment = $this->paymentService->findById($id);

        if (!$payment) {
            return $this->errorResponse(
                'Payment not found',
                404
            );
        }

        return match ($payment->payment_method) {
            'KHALTI' => $this->khaltiService->initiate($payment),
            'ESEWA' => $this->esewaService->initiate($payment),
            default => $this->errorResponse(
                'Unsupported payment method',
                400
            ),
        };
    }

    /**
     * eSewa success callback.
     */
    public function success(Request $request)
    {
        return $this->handleEsewaCallback($request);
    }

    /**
     * eSewa verification callback.
     */
    public function verify(Request $request)
    {
        return $this->handleEsewaCallback($request);
    }

    /**
     * eSewa failure callback.
     */
    public function failure()
    {
        return response()->json([
            'success' => false,
            'message' => 'Payment was cancelled or failed.',
        ], 400);
    }

    private function handleEsewaCallback(Request $request)
    {
        try {
            $data = $request->input('data')
                ?? $request->query('data');

            if (!$data) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing eSewa callback data.',
                ], 400);
            }

            /*
             * Decode/identify the payment here if required,
             * then call the main PaymentController verification
             * endpoint/service.
             *
             * Better approach: move this logic into PaymentService
             * or a dedicated PaymentVerificationService.
             */

            $result = $this->esewaService->verify([
                'data' => $data,
            ]);

            return response()->json([
                'success' => $result['status'] === 'SUCCESS',
                'message' => $result['status'] === 'SUCCESS'
                    ? 'Payment verified successfully.'
                    : 'Payment verification failed.',
                'status' => $result['status'],
                'transaction_id' => $result['transaction_id'] ?? null,
            ]);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}