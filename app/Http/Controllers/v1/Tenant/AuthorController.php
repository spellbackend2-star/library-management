<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Author\StoreAuthorRequest;
use App\Http\Requests\Author\UpdateAuthorRequest;
use App\Http\Resources\AuthorResource;
use App\Services\AuthorService;
use App\Traits\ResponseMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthorController extends Controller
{
    use ResponseMessage;

    public function __construct(
        protected AuthorService $authorService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'sort_by',
            'sort_order',
            'per_page',
        ]);

        $result = $this->authorService->getAll($filters);

        return $this->successResponse(
            AuthorResource::collection($result['data']),
            'Authors retrieved successfully.',
            200,
            $result['meta']
        );
    }

    public function store(StoreAuthorRequest $request): JsonResponse
    {
        $author = $this->authorService->create(
            $request->validated()
        );

        return $this->successResponse(
            new AuthorResource($author),
            'Author created successfully.',
            201
        );
    }

    public function show(int $author): JsonResponse
    {
        $authorData = $this->authorService->getById($author);

        abort_if(!$authorData, 404, 'Author not found.');

        return $this->successResponse(
            new AuthorResource($authorData),
            'Author retrieved successfully.'
        );
    }

    public function update(
        UpdateAuthorRequest $request,
        int $author
    ): JsonResponse {
        $authorData = $this->authorService->update(
            $author,
            $request->validated()
        );

        return $this->successResponse(
            new AuthorResource($authorData),
            'Author updated successfully.'
        );
    }

    public function destroy(int $author): JsonResponse
    {
        $this->authorService->delete($author);

        return $this->successResponse(
            null,
            'Author deleted successfully.'
        );
    }
}
