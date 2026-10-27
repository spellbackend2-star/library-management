<?php

namespace App\Http\Controllers\v1\Central;

use App\Http\Controllers\Controller;
use App\Http\Requests\Central\StoreCentralBlogRequest;
use App\Http\Requests\Central\UpdateCentralBlogRequest;
use App\Http\Resources\v1\Central\CentralBlogResource;
use App\Models\CentralBlog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CentralBlogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $blogs = CentralBlog::query()
            ->with('category')
            ->when($request->filled('is_published'), fn ($query) => $query->where('is_published', $request->boolean('is_published')))
            ->when($request->filled('category_id'), fn ($query) => $query->where('category_id', $request->integer('category_id')))
            ->latest('id')
            ->paginate(min(max($request->integer('per_page', 20), 1), 100));

        return response()->json([
            'success' => true,
            'data' => CentralBlogResource::collection($blogs->getCollection())->resolve(),
            'meta' => [
                'current_page' => $blogs->currentPage(),
                'per_page' => $blogs->perPage(),
                'total' => $blogs->total(),
                'last_page' => $blogs->lastPage(),
            ],
        ]);
    }

    public function store(StoreCentralBlogRequest $request): JsonResponse
    {
        $userId = $request->user('api')->id;
        $blog = CentralBlog::create([
            ...$request->validated(),
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        return response()->json([
            'success' => true,
            'data' => (new CentralBlogResource($blog->load('category')))->resolve(),
        ], 201);
    }

    public function show(CentralBlog $blog): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => (new CentralBlogResource($blog->load('category')))->resolve(),
        ]);
    }

    public function update(UpdateCentralBlogRequest $request, CentralBlog $blog): JsonResponse
    {
        $blog->update([
            ...$request->validated(),
            'updated_by' => $request->user('api')->id,
        ]);

        return response()->json([
            'success' => true,
            'data' => (new CentralBlogResource($blog->fresh()->load('category')))->resolve(),
        ]);
    }

    public function destroy(CentralBlog $blog): JsonResponse
    {
        $blog->delete();

        return response()->json(['success' => true, 'message' => 'Blog deleted.']);
    }
}
