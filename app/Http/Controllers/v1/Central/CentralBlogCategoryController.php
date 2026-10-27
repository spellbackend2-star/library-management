<?php

namespace App\Http\Controllers\v1\Central;

use App\Http\Controllers\Controller;
use App\Http\Requests\Central\StoreCentralBlogCategoryRequest;
use App\Http\Requests\Central\UpdateCentralBlogCategoryRequest;
use App\Http\Resources\v1\Central\CentralBlogCategoryResource;
use App\Models\CentralBlogCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CentralBlogCategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $categories = CentralBlogCategory::query()
            ->withCount('blogs')
            ->when($request->filled('is_active'), fn ($query) => $query->where('is_active', $request->boolean('is_active')))
            ->orderBy('name')
            ->paginate(min(max($request->integer('per_page', 20), 1), 100));

        return response()->json([
            'success' => true,
            'data' => CentralBlogCategoryResource::collection($categories->getCollection())->resolve(),
            'meta' => [
                'current_page' => $categories->currentPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
                'last_page' => $categories->lastPage(),
            ],
        ]);
    }

    public function store(StoreCentralBlogCategoryRequest $request): JsonResponse
    {
        $category = CentralBlogCategory::create($request->validated());

        return response()->json([
            'success' => true,
            'data' => (new CentralBlogCategoryResource($category))->resolve(),
        ], 201);
    }

    public function show(CentralBlogCategory $blogCategory): JsonResponse
    {
        $blogCategory->loadCount('blogs');

        return response()->json([
            'success' => true,
            'data' => (new CentralBlogCategoryResource($blogCategory))->resolve(),
        ]);
    }

    public function update(UpdateCentralBlogCategoryRequest $request, CentralBlogCategory $blogCategory): JsonResponse
    {
        $blogCategory->update($request->validated());

        return response()->json([
            'success' => true,
            'data' => (new CentralBlogCategoryResource($blogCategory->fresh()))->resolve(),
        ]);
    }

    public function destroy(CentralBlogCategory $blogCategory): JsonResponse
    {
        $blogCategory->delete();

        return response()->json(['success' => true, 'message' => 'Blog category deleted.']);
    }
}
