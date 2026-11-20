<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Copy\StoreCopyRequest;
use App\Http\Requests\Copy\UpdateCopyRequest;
use App\Http\Resources\CopyResource;
use App\Services\CopyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CopyController extends Controller
{
    public function __construct(
        protected CopyService $copyService
    ) {}

    public function index(Request $request)
    {
        $filters = $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'barcode' => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);
        $result = $this->copyService->getAll($filters);
        $barcodeSearch = isset($filters['barcode']) && $filters['barcode'] !== '';
        $message = ! $barcodeSearch
            ? 'Copies retrieved successfully.'
            : ($result['meta']['total'] > 0
                ? 'Copy found successfully.'
                : 'No copy found for this barcode.');

        return CopyResource::collection($result['data'])
            ->additional([
                'success' => true,
                'message' => $message,
                'meta' => $result['meta'],
            ]);
    }

    public function store(StoreCopyRequest $request): CopyResource
    {
        $copy = $this->copyService->create(
            $request->validated()
        );

        return new CopyResource($copy);
    }

    public function show(int $copy): CopyResource
    {
        $copyData = $this->copyService->getById($copy);

        abort_if(! $copyData, 404, 'Copy not found.');

        return new CopyResource($copyData);
    }

    public function update(
        UpdateCopyRequest $request,
        int $copy
    ): CopyResource {
        $copyData = $this->copyService->update(
            $copy,
            $request->validated()
        );

        return new CopyResource($copyData);
    }

    public function destroy(int $copy): JsonResponse
    {
        $this->copyService->delete($copy);

        return response()->json([
            'message' => 'Copy deleted successfully.',
        ]);
    }
}
