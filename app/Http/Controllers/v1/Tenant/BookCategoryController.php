<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\BookCategory\StoreBookCategoryRequest;
use App\Http\Requests\BookCategory\UpdateBookCategoryRequest;
use App\Http\Resources\BookCategoryResource;
use App\Services\BookCategoryService;
use Illuminate\Http\JsonResponse;

class BookCategoryController extends Controller
{
    public function __construct(
        protected BookCategoryService $bookCategoryService
    ) {}

    public function index()
    {
        return BookCategoryResource::collection(
            $this->bookCategoryService->getAll()
        );
    }

    public function store(StoreBookCategoryRequest $request): BookCategoryResource
    {
        $bookCategory = $this->bookCategoryService->create(
            $request->validated()
        );

        return new BookCategoryResource($bookCategory);
    }

    public function show(int $book_category): BookCategoryResource
    {
        $bookCategoryData = $this->bookCategoryService->getById($book_category);

        abort_if(!$bookCategoryData, 404, 'Book category not found.');

        return new BookCategoryResource($bookCategoryData);
    }

    public function update(
        UpdateBookCategoryRequest $request,
        int $book_category
    ): BookCategoryResource {
        $bookCategoryData = $this->bookCategoryService->update(
            $book_category,
            $request->validated()
        );

        return new BookCategoryResource($bookCategoryData);
    }

    public function destroy(int $book_category): JsonResponse
    {
        $this->bookCategoryService->delete($book_category);

        return response()->json([
            'message' => 'Book category deleted successfully.',
        ]);
    }
}
