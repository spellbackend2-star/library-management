<?php

namespace App\Http\Controllers\v1\Central;

use App\Http\Controllers\Controller;
use App\Http\Resources\CentralInvoiceResource;
use App\Repositories\Interface\CentralInvoiceInterface;
use App\Traits\ResponseMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CentralInvoiceController extends Controller
{
    use ResponseMessage;

    public function __construct(
        protected CentralInvoiceInterface $centralInvoiceRepository
    ) {}

    /**
     * Get the central invoices.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'tenant_id',
            'subscription_id',
            'status',
            'invoice_type',
            'from_date',
            'to_date',
            'per_page',
            'sort_by',
            'sort_order',
        ]);

        $result = $this->centralInvoiceRepository->getAll($filters);

        return $this->successResponse(
            CentralInvoiceResource::collection($result['data']),
            'Central invoices retrieved successfully.',
            200,
            $result['meta']
        );
    }

    /**
     * Get a single central invoice.
     */
    public function show(int $id): JsonResponse
    {
        $centralInvoice = $this->centralInvoiceRepository->find($id);

        if (! $centralInvoice) {
            return $this->errorResponse(
                'Central invoice not found.',
                404
            );
        }

        return $this->successResponse(
            new CentralInvoiceResource($centralInvoice->load(['tenant', 'subscription.plan', 'subscriptionPayment'])),
            'Central invoice retrieved successfully.'
        );
    }

    /**
     * Get a single central invoice by invoice number.
     */
    public function showByNumber(string $invoiceNumber): JsonResponse
    {
        $centralInvoice = $this->centralInvoiceRepository->findByNumber($invoiceNumber);

        if (! $centralInvoice) {
            return $this->errorResponse(
                'Central invoice not found.',
                404
            );
        }

        return $this->successResponse(
            new CentralInvoiceResource($centralInvoice->load(['tenant', 'subscription.plan', 'subscriptionPayment'])),
            'Central invoice retrieved successfully.'
        );
    }
}
