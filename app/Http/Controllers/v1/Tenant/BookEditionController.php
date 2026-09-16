<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\BookEdition\StoreBookEditionRequest;
use App\Http\Requests\BookEdition\UpdateBookEditionRequest;
use App\Http\Resources\BookEditionResource;
use App\Services\BookEditionService;
use App\Traits\ResponseMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookEditionController extends Controller
{
    use ResponseMessage;

    public function __construct(
        protected BookEditionService $bookEditionService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'sort_by',
            'sort_order',
            'per_page',
        ]);

        $result = $this->bookEditionService->getAll($filters);

        return $this->successResponse(
            BookEditionResource::collection($result['data']),
            'Book editions retrieved successfully.',
            200,
            $result['meta']
        );
    }

    public function store(StoreBookEditionRequest $request): JsonResponse
    {
        $edition = $this->bookEditionService->create(
            $request->validated()
        );

        return $this->successResponse(
            new BookEditionResource($edition),
            'Book edition created successfully.',
            201
        );
    }

    public function show(int $book_edition): JsonResponse
    {
        $editionData = $this->bookEditionService->getById($book_edition);

        abort_if(!$editionData, 404, 'Book edition not found.');

        return $this->successResponse(
            new BookEditionResource($editionData),
            'Book edition retrieved successfully.'
        );
    }

    public function update(
        UpdateBookEditionRequest $request,
        int $book_edition
    ): JsonResponse {
        $editionData = $this->bookEditionService->update(
            $book_edition,
            $request->validated()
        );

        return $this->successResponse(
            new BookEditionResource($editionData),
            'Book edition updated successfully.'
        );
    }

    public function destroy(int $book_edition): JsonResponse
    {
        $this->bookEditionService->delete($book_edition);

        return $this->successResponse(
            null,
            'Book edition deleted successfully.'
        );
    }
}
