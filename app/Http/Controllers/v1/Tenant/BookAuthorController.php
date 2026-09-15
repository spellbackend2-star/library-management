<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\BookAuthor\StoreBookAuthorRequest;
use App\Http\Requests\BookAuthor\UpdateBookAuthorRequest;
use App\Http\Resources\BookAuthorResource;
use App\Services\BookAuthorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookAuthorController extends Controller
{
    public function __construct(
        protected BookAuthorService $bookAuthorService
    ) {}

    public function index(Request $request)
    {
        $filters = $request->only(['per_page']);
        $result = $this->bookAuthorService->getAll($filters);

        return BookAuthorResource::collection($result['data'])
            ->additional(['meta' => $result['meta']]);
    }

    public function store(StoreBookAuthorRequest $request): BookAuthorResource
    {
        $bookAuthor = $this->bookAuthorService->create(
            $request->validated()
        );

        return new BookAuthorResource($bookAuthor);
    }

    public function show(Request $request, int $book_author)
    {
        $filters = $request->only(['per_page']);
        $filters['id'] = $book_author;

        $result = $this->bookAuthorService->getAll($filters);

        abort_if(empty($result['data']), 404, 'Book author not found.');

        return BookAuthorResource::collection($result['data'])
            ->additional(['meta' => $result['meta']]);
    }

    public function update(
        UpdateBookAuthorRequest $request,
        int $book_author
    ): BookAuthorResource {
        $bookAuthorData = $this->bookAuthorService->update(
            $book_author,
            $request->validated()
        );

        return new BookAuthorResource($bookAuthorData);
    }

    public function destroy(int $book_author): JsonResponse
    {
        $this->bookAuthorService->delete($book_author);

        return response()->json([
            'message' => 'Book author deleted successfully.',
        ]);
    }
}
