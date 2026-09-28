<?php

namespace App\Http\Controllers\v1\Central;

use App\Http\Controllers\Controller;
use App\Http\Resources\CentralInvoiceResource;
use App\Services\CentralInvoiceService;
use App\Traits\ResponseMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CentralInvoiceController extends Controller
{
    use ResponseMessage;

    public function __construct(
        protected CentralInvoiceService $centralInvoiceService
    ) {}

    /**
     * Get the central invoices.
     */
    public function index(Request $request): JsonResponse
    {
        $result = $this->centralInvoiceService->getAll(
            $request->all()
        );

        return response()->json([
            'success' => true,
            'message' => 'Central invoices retrieved successfully.',
            'data' => CentralInvoiceResource::collection($result['data']),
            'meta' => $result['meta'],
        ]);
    }

    /**
     * Get a single central invoice.
     */
    public function show(int $id): JsonResponse
    {
        $centralInvoice = $this->centralInvoiceService->getById($id);

        if (! $centralInvoice) {
            return $this->errorResponse(
                'Central invoice not found.',
                404
            );
        }

        return $this->successResponse(
            new CentralInvoiceResource($centralInvoice),
            'Central invoice retrieved successfully.'
        );
    }

    /**
     * Get a single central invoice by invoice number.
     */
    public function showByNumber(string $invoiceNumber): JsonResponse
    {
        $centralInvoice = $this->centralInvoiceService->getByNumber(
            $invoiceNumber
        );

        if (! $centralInvoice) {
            return $this->errorResponse(
                'Central invoice not found.',
                404
            );
        }

        return $this->successResponse(
            new CentralInvoiceResource($centralInvoice),
            'Central invoice retrieved successfully.'
        );
    }
}
