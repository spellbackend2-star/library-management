<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Book\StoreBookRequest;
use App\Http\Requests\Book\UpdateBookRequest;
use App\Http\Resources\BookResource;
use App\Services\BookService;
use Illuminate\Http\JsonResponse;

class BookController extends Controller
{
    public function __construct(
        protected BookService $bookService
    ) {}

    public function index()
    {
        return BookResource::collection(
            $this->bookService->getAll()
        );
    }

    public function store(StoreBookRequest $request): BookResource
    {
        $payload = $request->validated();

        $hasNested = isset($payload['author_ids'])
            || isset($payload['category_ids'])
            || isset($payload['editions']);

        $book = $hasNested
            ? $this->bookService->createWithRelations($payload)
            : $this->bookService->create($payload);

        return new BookResource($book);
    }

    public function show(int $book): BookResource
    {
        $bookData = $this->bookService->getByIdWithRelations($book);

        abort_if(!$bookData, 404, 'Book not found.');

        return new BookResource($bookData);
    }

    public function update(
        UpdateBookRequest $request,
        int $book
    ): BookResource {
        $payload = $request->validated();

        $hasNested = isset($payload['author_ids'])
            || isset($payload['category_ids'])
            || isset($payload['editions']);

        $bookData = $hasNested
            ? $this->bookService->updateWithRelations($book, $payload)
            : $this->bookService->update($book, $payload);

        return new BookResource($bookData);
    }

    public function destroy(int $book): JsonResponse
    {
        $deleted = $this->bookService->delete($book);

        abort_if(!$deleted, 404, 'Book not found.');

        return response()->json([
            'message' => 'Book, all its editions, copies, and pivot links were permanently deleted.',
        ]);
    }
}
