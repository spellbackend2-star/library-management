<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    public function index(Request $request)
    {
        $filters = $request->only([
            'status',
            'payment_method',
            'member_id',
            'booking_id',
        ]);

        return PaymentResource::collection(
            $this->paymentService->getAll($filters)
        );
    }

    public function store(Request $request)
    {
        $booking = Booking::findOrFail($request->input('booking_id'));

        $result = $this->paymentService->createFromBooking(
            $booking,
            $request->only([
                'payment_method',
                'currency',
            ])
        );

        return response()->json([
            'data' => new PaymentResource($result['payment']),
            'gateway' => $result['gateway'] ?? null,
            'status' => $result['status'],
        ]);
    }

    public function show(Payment $payment)
    {
        $payment = $this->paymentService->findById($payment->id);

        return new PaymentResource($payment);
    }

    public function destroy(Payment $payment): JsonResponse
    {
        $payment->delete();

        return response()->json([
            'message' => 'Payment deleted successfully.',
        ]);
    }
}
