<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\BookEdition\StoreBookEditionRequest;
use App\Http\Requests\BookEdition\UpdateBookEditionRequest;
use App\Http\Resources\BookEditionResource;
use App\Services\BookEditionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookEditionController extends Controller
{
    public function __construct(
        protected BookEditionService $bookEditionService
    ) {}

    public function index(Request $request)
    {
        $filters = $request->only(['per_page']);
        $result = $this->bookEditionService->getAll($filters);

        return BookEditionResource::collection($result['data'])
            ->additional(['meta' => $result['meta']]);
    }

    public function store(StoreBookEditionRequest $request): BookEditionResource
    {
        $edition = $this->bookEditionService->create(
            $request->validated()
        );

        return new BookEditionResource($edition);
    }

    public function show(Request $request, int $book_edition)
    {
        $filters = $request->only(['per_page']);
        $filters['id'] = $book_edition;

        $result = $this->bookEditionService->getAll($filters);

        abort_if(empty($result['data']), 404, 'Book edition not found.');

        return BookEditionResource::collection($result['data'])
            ->additional(['meta' => $result['meta']]);
    }

    public function update(
        UpdateBookEditionRequest $request,
        int $book_edition
    ): BookEditionResource {
        $editionData = $this->bookEditionService->update(
            $book_edition,
            $request->validated()
        );

        return new BookEditionResource($editionData);
    }

    public function destroy(int $book_edition): JsonResponse
    {
        $this->bookEditionService->delete($book_edition);

        return response()->json([
            'message' => 'Book edition deleted successfully.',
        ]);
    }
}
