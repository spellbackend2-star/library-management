<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Invoice\AddPaymentRequest;
use App\Http\Requests\Invoice\StoreInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\PaymentResource;
use App\Services\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(
        protected InvoiceService $invoiceService
    ) {}

    public function index(Request $request)
    {
        $invoices = $this->invoiceService->getAll();

        return InvoiceResource::collection($invoices);
    }

    public function show(int $id)
    {
        $invoice = $this->invoiceService->findById($id);

        if (! $invoice) {
            return response()->json(['message' => 'Invoice not found.'], 404);
        }

        return new InvoiceResource($invoice);
    }

    public function store(StoreInvoiceRequest $request): InvoiceResource
    {
        $invoice = $this->invoiceService->create(
            $request->validated()
        );

        return new InvoiceResource($invoice);
    }

    public function addPayment(int $id, AddPaymentRequest $request): JsonResponse
    {
        $result = $this->invoiceService->addPayment(
            $id,
            $request->validated()
        );

        if (is_array($result) && ($result['status'] ?? null) === 'already_paid') {
            $payment = $result['payment'];
            $invoice = $payment->invoice;

            return response()->json([
                'success' => true,
                'message' => 'Payment already completed for this invoice.',
                'payment' => new PaymentResource($payment),
                'invoice' => [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'total_amount' => $invoice->total_amount,
                    'coupon_discount' => $invoice->coupon_discount,
                    'paid_amount' => $invoice->paid_amount,
                    'remaining_amount' => number_format((float) $invoice->total_amount - (float) $invoice->paid_amount - (float) $invoice->coupon_discount, 2, '.', ''),
                    'status' => $invoice->status,
                ],
            ], 200);
        }

        if (is_array($result) && ($result['gateway'] ?? false) === true) {
            $payment = $result['payment'];
            $invoice = $payment->invoice;

            // Calculate effective remaining including this pending payment
            $effectivePaidAmount = (float) $invoice->paid_amount + (float) $payment->amount;
            $effectiveRemaining = max(0, (float) $invoice->total_amount - $effectivePaidAmount - (float) $invoice->coupon_discount);

            return response()->json([
                'success' => true,
                'message' => 'Payment initiated. Redirect to payment URL.',
                'payment' => new PaymentResource($payment),
                'payment_url' => $payment->payment_url,
                'transaction_id' => $payment->gateway_reference,
                'invoice' => [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'total_amount' => $invoice->total_amount,
                    'coupon_discount' => $invoice->coupon_discount,
                    'paid_amount' => $invoice->paid_amount,
                    'pending_amount' => $payment->amount,
                    'effective_paid_amount' => round($effectivePaidAmount, 2),
                    'remaining_amount' => number_format($effectiveRemaining, 2, '.', ''),
                    'status' => $invoice->status,
                ],
            ], 201);
        }

        $payment = $result;
        $invoice = $payment->invoice;

        return response()->json([
            'success' => true,
            'message' => 'Payment successful.',
            'payment' => new PaymentResource($payment),
            'transaction_id' => $payment->transaction_id,
            'invoice' => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'total_amount' => $invoice->total_amount,
                'coupon_discount' => $invoice->coupon_discount,
                'paid_amount' => $invoice->paid_amount,
                'remaining_amount' => number_format((float) $invoice->total_amount - (float) $invoice->paid_amount - (float) $invoice->coupon_discount, 2, '.', ''),
                'status' => $invoice->status,
            ],
        ], 201);
    }

    public function byMember(int $memberId)
    {
        $invoice = $this->invoiceService->findByMember($memberId);

        if (! $invoice) {
            return response()->json(['message' => 'No unpaid invoice found for member.'], 404);
        }

        return new InvoiceResource($invoice);
    }
}
