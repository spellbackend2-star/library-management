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
        $book = $this->bookService->create(
            $request->validated()
        );

        return new BookResource($book);
    }

    public function show(int $book): BookResource
    {
        $bookData = $this->bookService->getById($book);

        abort_if(!$bookData, 404, 'Book not found.');

        return new BookResource($bookData);
    }

    public function update(
        UpdateBookRequest $request,
        int $book
    ): BookResource {
        $bookData = $this->bookService->update(
            $book,
            $request->validated()
        );

        return new BookResource($bookData);
    }

    public function destroy(int $book): JsonResponse
    {
        $this->bookService->delete($book);

        return response()->json([
            'message' => 'Book deleted successfully.',
        ]);
    }
}
