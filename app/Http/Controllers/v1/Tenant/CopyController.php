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
        $filters = $request->only(['per_page']);
        $result = $this->copyService->getAll($filters);

        return CopyResource::collection($result['data'])
            ->additional(['meta' => $result['meta']]);
    }

    public function store(StoreCopyRequest $request): CopyResource
    {
        $copy = $this->copyService->create(
            $request->validated()
        );

        return new CopyResource($copy);
    }

    public function show(Request $request, int $copy)
    {
        $filters = $request->only(['per_page']);
        $filters['id'] = $copy;

        $result = $this->copyService->getAll($filters);

        abort_if(empty($result['data']), 404, 'Copy not found.');

        return CopyResource::collection($result['data'])
            ->additional(['meta' => $result['meta']]);
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
