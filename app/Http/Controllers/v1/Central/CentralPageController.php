<?php

namespace App\Http\Controllers\v1\Central;

use App\Http\Controllers\Controller;
use App\Http\Requests\Central\StoreCentralPageRequest;
use App\Http\Requests\Central\UpdateCentralPageRequest;
use App\Http\Resources\v1\Central\CentralPageResource;
use App\Models\CentralPage;
use App\Services\Central\CentralPageService;
use App\Traits\ResponseMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CentralPageController extends Controller
{
    use ResponseMessage;

    public function __construct(
        protected CentralPageService $centralPageService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $pages = $this->centralPageService->getAll(
            $request->only(['per_page', 'page'])
        );

        return $this->successResponse(
            CentralPageResource::collection($pages),
            'Central pages retrieved successfully.',
            200,
            [
                'total' => $pages->total(),
                'last_page' => $pages->lastPage(),
                'current_page' => $pages->currentPage(),
                'per_page' => $pages->perPage(),
                'first_page_url' => $pages->url(1),
                'last_page_url' => $pages->url($pages->lastPage()),
                'next_page_url' => $pages->nextPageUrl(),
                'prev_page_url' => $pages->previousPageUrl(),
            ]
        );
    }

    public function store(StoreCentralPageRequest $request): JsonResponse
    {
        $page = $this->centralPageService->create($request->validated());

        return $this->successResponse(
            new CentralPageResource($page),
            'Central page created successfully.',
            201
        );
    }

    public function show(CentralPage $page): JsonResponse
    {
        $page->load(['creator', 'updater']);

        return $this->successResponse(
            new CentralPageResource($page),
            'Central page retrieved successfully.'
        );
    }

    public function update(
        UpdateCentralPageRequest $request,
        CentralPage $page
    ): JsonResponse {
        $updatedPage = $this->centralPageService->update($page, $request->validated());

        return $this->successResponse(
            new CentralPageResource($updatedPage),
            'Central page updated successfully.'
        );
    }

    public function destroy(CentralPage $page): JsonResponse
    {
        $this->centralPageService->delete($page);

        return $this->successResponse(
            null,
            'Central page deleted successfully.'
        );
    }
}
