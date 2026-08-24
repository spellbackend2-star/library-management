<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function __construct(
        protected CategoryService $categoryService
    ) {}

    public function index()
    {
        return CategoryResource::collection(
            $this->categoryService->getAll()
        );
    }

    public function store(StoreCategoryRequest $request): CategoryResource
    {
        $category = $this->categoryService->create(
            $request->validated()
        );

        return new CategoryResource($category);
    }

    public function show(int $category): CategoryResource
    {
        $categoryData = $this->categoryService->getById($category);

        abort_if(!$categoryData, 404, 'Category not found.');

        return new CategoryResource($categoryData);
    }

    public function update(
        UpdateCategoryRequest $request,
        int $category
    ): CategoryResource {
        $categoryData = $this->categoryService->update(
            $category,
            $request->validated()
        );

        return new CategoryResource($categoryData);
    }

    public function destroy(int $category): JsonResponse
    {
        $this->categoryService->delete($category);

        return response()->json([
            'message' => 'Category deleted successfully.',
        ]);
    }
}
