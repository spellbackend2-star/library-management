<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Book\AddCopyToBookRequest;
use App\Http\Requests\Book\StoreBookRequest;
use App\Http\Requests\Book\UpdateBookRequest;
use App\Http\Requests\Book\UpdateCopyForBookRequest;
use App\Http\Resources\BookResource;
use App\Http\Resources\CopyResource;
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

    public function addCopies(
        AddCopyToBookRequest $request,
        int $book
    ): JsonResponse {
        $payload = $request->validated();

        $result = $this->bookService->addCopies(
            bookId: $book,
            editionId: $payload['edition_id'],
            copies: $payload['copies'],
        );

        return response()->json([
            'message' => count($result['created_copies']) . ' copy/copies added successfully.',
            'book' => new BookResource($result['book']),
            'created_copies' => CopyResource::collection(collect($result['created_copies'])),
        ], 201);
    }

    public function listCopies(int $book): JsonResponse
    {
        try {
            $copies = $this->bookService->listCopies($book);
        } catch (\Exception $e) {
            abort(404, $e->getMessage());
        }

        return response()->json([
            'book_id' => $book,
            'copies' => CopyResource::collection($copies),
        ]);
    }

    public function showCopy(int $book, int $copy): CopyResource
    {
        try {
            $row = $this->bookService->getCopy($book, $copy);
        } catch (\Exception $e) {
            abort(404, $e->getMessage());
        }

        return new CopyResource($row);
    }

    public function updateCopy(
        UpdateCopyForBookRequest $request,
        int $book,
        int $copy
    ): CopyResource {
        try {
            $updated = $this->bookService->updateCopy(
                bookId: $book,
                copyId: $copy,
                data: $request->validated(),
            );
        } catch (\Exception $e) {
            abort(404, $e->getMessage());
        }

        return new CopyResource($updated);
    }

    public function deleteCopy(int $book, int $copy): JsonResponse
    {
        try {
            $deleted = $this->bookService->deleteCopy(
                bookId: $book,
                copyId: $copy,
            );
        } catch (\Exception $e) {
            abort(404, $e->getMessage());
        }

        return response()->json([
            'message' => $deleted
                ? 'Copy deleted successfully.'
                : 'Copy could not be deleted.',
        ]);
    }
}
