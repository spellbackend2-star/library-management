<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct(
        protected CategoryService $categoryService
    ) {}

    public function index(Request $request)
    {
         $filters = $request->only([
            'search',
            'name',
            'sort_by',
            'sort_order',
            'per_page',
        ]);

        $result = $this->categoryService->getAll($filters);
        return CategoryResource::collection($result['data'])
            ->additional(['meta' => $result['meta']]);
    }
    public function show(Request $request, int $category)
    {
        $filters = $request->only([
            'search',
            'name',
            'sort_by',
            'sort_order',
            'per_page',
        ]);

        $filters['id'] = $category;

        $result = $this->categoryService->getAll($filters);

        abort_if(empty($result['data']), 404, 'Category not found.');

        return CategoryResource::collection($result['data'])
            ->additional(['meta' => $result['meta']]);
    }

    public function store(StoreCategoryRequest $request): CategoryResource
    {
        $category = $this->categoryService->create(
            $request->validated()
        );

        return new CategoryResource($category);
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
