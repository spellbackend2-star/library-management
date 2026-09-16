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
use App\Traits\ResponseMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookController extends Controller
{
    use ResponseMessage;

    public function __construct(
        protected BookService $bookService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $result = $this->bookService->getAll($request->all());

        return $this->successResponse(
            BookResource::collection($result['data']),
            'Books retrieved successfully.',
            200,
            $result['meta']
        );
    }

    public function store(StoreBookRequest $request): JsonResponse
    {
        $payload = $request->validated();

        $hasNested = isset($payload['author_ids'])
            || isset($payload['category_ids'])
            || isset($payload['editions']);

        $book = $hasNested
            ? $this->bookService->createWithRelations($payload)
            : $this->bookService->create($payload);

        return $this->successResponse(
            new BookResource($book),
            'Book created successfully.',
            201
        );
    }

    public function show(int $book): JsonResponse
    {
        $bookData = $this->bookService->getByIdWithRelations($book);

        abort_if(!$bookData, 404, 'Book not found.');

        return $this->successResponse(
            new BookResource($bookData),
            'Book retrieved successfully.'
        );
    }

    public function update(
        UpdateBookRequest $request,
        int $book
    ): JsonResponse {
        $payload = $request->validated();

        $hasNested = isset($payload['author_ids'])
            || isset($payload['category_ids'])
            || isset($payload['editions']);

        $bookData = $hasNested
            ? $this->bookService->updateWithRelations($book, $payload)
            : $this->bookService->update($book, $payload);

        return $this->successResponse(
            new BookResource($bookData),
            'Book updated successfully.'
        );
    }

    public function destroy(int $book): JsonResponse
    {
        $deleted = $this->bookService->delete($book);

        abort_if(!$deleted, 404, 'Book not found.');

        return $this->successResponse(
            null,
            'Book, all its editions, copies, and pivot links were permanently deleted.'
        );
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

        return $this->successResponse(
            [
                'book' => new BookResource($result['book']),
                'created_copies' => CopyResource::collection(collect($result['created_copies'])),
            ],
            count($result['created_copies']) . ' copy/copies added successfully.',
            201
        );
    }

    public function listCopies(Request $request, int $book): JsonResponse
    {
        try {
            $result = $this->bookService->listCopies(
                $book,
                $request->only(['per_page'])
            );
        } catch (\Exception $e) {
            abort(404, $e->getMessage());
        }

        return $this->successResponse(
            CopyResource::collection($result['data']),
            'Copies retrieved successfully.',
            200,
            $result['meta']
        );
    }

    public function showCopy(int $book, int $copy): JsonResponse
    {
        try {
            $row = $this->bookService->getCopy($book, $copy);
        } catch (\Exception $e) {
            abort(404, $e->getMessage());
        }

        return $this->successResponse(
            new CopyResource($row),
            'Copy retrieved successfully.'
        );
    }

    public function updateCopy(
        UpdateCopyForBookRequest $request,
        int $book,
        int $copy
    ): JsonResponse {
        try {
            $updated = $this->bookService->updateCopy(
                bookId: $book,
                copyId: $copy,
                data: $request->validated(),
            );
        } catch (\Exception $e) {
            abort(404, $e->getMessage());
        }

        return $this->successResponse(
            new CopyResource($updated),
            'Copy updated successfully.'
        );
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

        return $this->successResponse(
            null,
            $deleted ? 'Copy deleted successfully.' : 'Copy could not be deleted.'
        );
    }
}
