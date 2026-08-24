<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\BookAuthor\StoreBookAuthorRequest;
use App\Http\Requests\BookAuthor\UpdateBookAuthorRequest;
use App\Http\Resources\BookAuthorResource;
use App\Services\BookAuthorService;
use Illuminate\Http\JsonResponse;

class BookAuthorController extends Controller
{
    public function __construct(
        protected BookAuthorService $bookAuthorService
    ) {}

    public function index()
    {
        return BookAuthorResource::collection(
            $this->bookAuthorService->getAll()
        );
    }

    public function store(StoreBookAuthorRequest $request): BookAuthorResource
    {
        $bookAuthor = $this->bookAuthorService->create(
            $request->validated()
        );

        return new BookAuthorResource($bookAuthor);
    }

    public function show(int $book_author): BookAuthorResource
    {
        $bookAuthorData = $this->bookAuthorService->getById($book_author);

        abort_if(!$bookAuthorData, 404, 'Book author not found.');

        return new BookAuthorResource($bookAuthorData);
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
