<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Publisher\StorePublisherRequest;
use App\Http\Requests\Publisher\UpdatePublisherRequest;
use App\Http\Resources\PublisherResource;
use App\Services\PublisherService;
use Illuminate\Http\JsonResponse;

class PublisherController extends Controller
{
    public function __construct(
        protected PublisherService $publisherService
    ) {}

    public function index()
    {
        return PublisherResource::collection(
            $this->publisherService->getAll()
        );
    }

    public function store(StorePublisherRequest $request): PublisherResource
    {
        $publisher = $this->publisherService->create(
            $request->validated()
        );

        return new PublisherResource($publisher);
    }

    public function show(int $publisher): PublisherResource
    {
        $publisherData = $this->publisherService->getById($publisher);

        abort_if(!$publisherData, 404, 'Publisher not found.');

        return new PublisherResource($publisherData);
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
