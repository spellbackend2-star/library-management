<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\BookEdition\StoreBookEditionRequest;
use App\Http\Requests\BookEdition\UpdateBookEditionRequest;
use App\Http\Resources\BookEditionResource;
use App\Services\BookEditionService;
use Illuminate\Http\JsonResponse;

class BookEditionController extends Controller
{
    public function __construct(
        protected BookEditionService $bookEditionService
    ) {}

    public function index()
    {
        return BookEditionResource::collection(
            $this->bookEditionService->getAll()
        );
    }

    public function store(StoreBookEditionRequest $request): BookEditionResource
    {
        $edition = $this->bookEditionService->create(
            $request->validated()
        );

        return new BookEditionResource($edition);
    }

    public function show(int $book_edition): BookEditionResource
    {
        $editionData = $this->bookEditionService->getById($book_edition);

        abort_if(!$editionData, 404, 'Book edition not found.');

        return new BookEditionResource($editionData);
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
