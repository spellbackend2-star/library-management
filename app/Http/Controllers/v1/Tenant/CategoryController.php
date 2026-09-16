<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Services\CategoryService;
use App\Traits\ResponseMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use ResponseMessage;

    public function __construct(
        protected CategoryService $categoryService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search',
            'name',
            'sort_by',
            'sort_order',
            'per_page',
        ]);

        $result = $this->categoryService->getAll($filters);

        return $this->successResponse(
            CategoryResource::collection($result['data']),
            'Categories retrieved successfully.',
            200,
            $result['meta']
        );
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = $this->categoryService->create(
            $request->validated()
        );

        return $this->successResponse(
            new CategoryResource($category),
            'Category created successfully.',
            201
        );
    }

    public function show(int $category): JsonResponse
    {
        $categoryData = $this->categoryService->getById($category);

        abort_if(!$categoryData, 404, 'Category not found.');

        return $this->successResponse(
            new CategoryResource($categoryData),
            'Category retrieved successfully.'
        );
    }

    public function update(
        UpdateCategoryRequest $request,
        int $category
    ): JsonResponse {
        $categoryData = $this->categoryService->update(
            $category,
            $request->validated()
        );

        return $this->successResponse(
            new CategoryResource($categoryData),
            'Category updated successfully.'
        );
    }

    public function destroy(int $category): JsonResponse
    {
        $this->categoryService->delete($category);

        return $this->successResponse(
            null,
            'Category deleted successfully.'
        );
    }
}
