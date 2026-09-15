<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Publisher\StorePublisherRequest;
use App\Http\Requests\Publisher\UpdatePublisherRequest;
use App\Http\Resources\PublisherResource;
use App\Services\PublisherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublisherController extends Controller
{
    public function __construct(
        protected PublisherService $publisherService
    ) {}

    public function index(Request $request)
    {
        $filters = $request->only([
            'search',
            'name',
            'sort_by',
            'sort_order',
            'per_page',
        ]);

        $result = $this->publisherService->getAll($filters);

        return PublisherResource::collection($result['data'])
            ->additional(['meta' => $result['meta']]);
    }

    public function store(StorePublisherRequest $request): PublisherResource
    {
        $publisher = $this->publisherService->create(
            $request->validated()
        );

        return new PublisherResource($publisher);
    }

    public function show(Request $request, int $publisher)
    {
        $filters = $request->only([
            'search',
            'name',
            'sort_by',
            'sort_order',
            'per_page',
        ]);

        $filters['id'] = $publisher;

        $result = $this->publisherService->getAll($filters);

        abort_if(empty($result['data']), 404, 'Publisher not found.');

        return PublisherResource::collection($result['data'])
            ->additional(['meta' => $result['meta']]);
    }

    public function update(
        UpdatePublisherRequest $request,
        int $publisher
    ): PublisherResource {
        $publisherData = $this->publisherService->update(
            $publisher,
            $request->validated()
        );

        return new PublisherResource($publisherData);
    }

    public function destroy(int $publisher): JsonResponse
    {
        $this->publisherService->delete($publisher);

        return response()->json([
            'message' => 'Publisher deleted successfully.',
        ]);
    }
}
