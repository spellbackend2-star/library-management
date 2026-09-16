<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Publisher\StorePublisherRequest;
use App\Http\Requests\Publisher\UpdatePublisherRequest;
use App\Http\Resources\PublisherResource;
use App\Services\PublisherService;
use App\Traits\ResponseMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublisherController extends Controller
{
    use ResponseMessage;

    public function __construct(
        protected PublisherService $publisherService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search',
            'name',
            'sort_by',
            'sort_order',
            'per_page',
        ]);

        $result = $this->publisherService->getAll($filters);

        return $this->successResponse(
            PublisherResource::collection($result['data']),
            'Publishers retrieved successfully.',
            200,
            $result['meta']
        );
    }

    public function store(StorePublisherRequest $request): JsonResponse
    {
        $publisher = $this->publisherService->create(
            $request->validated()
        );

        return $this->successResponse(
            new PublisherResource($publisher),
            'Publisher created successfully.',
            201
        );
    }

    public function show(int $publisher): JsonResponse
    {
        $publisherData = $this->publisherService->getById($publisher);

        abort_if(!$publisherData, 404, 'Publisher not found.');

        return $this->successResponse(
            new PublisherResource($publisherData),
            'Publisher retrieved successfully.'
        );
    }

    public function update(
        UpdatePublisherRequest $request,
        int $publisher
    ): JsonResponse {
        $publisherData = $this->publisherService->update(
            $publisher,
            $request->validated()
        );

        return $this->successResponse(
            new PublisherResource($publisherData),
            'Publisher updated successfully.'
        );
    }

    public function destroy(int $publisher): JsonResponse
    {
        $this->publisherService->delete($publisher);

        return $this->successResponse(
            null,
            'Publisher deleted successfully.'
        );
    }
}
