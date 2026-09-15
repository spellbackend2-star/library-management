<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Author\StoreAuthorRequest;
use App\Http\Requests\Author\UpdateAuthorRequest;
use App\Http\Resources\AuthorResource;
use App\Services\AuthorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthorController extends Controller
{
    public function __construct(
        protected AuthorService $authorService
    ) {}

    public function index(Request $request)
    {
        $filters = $request->only(['per_page']);
        $result = $this->authorService->getAll($filters);

        return AuthorResource::collection($result['data'])
            ->additional(['meta' => $result['meta']]);
    }

    public function store(StoreAuthorRequest $request): AuthorResource
    {
        $author = $this->authorService->create(
            $request->validated()
        );

        return new AuthorResource($author);
    }

    public function show(Request $request, int $author)
    {
        $filters = $request->only(['per_page']);
        $filters['id'] = $author;

        $result = $this->authorService->getAll($filters);

        abort_if(empty($result['data']), 404, 'Author not found.');

        return AuthorResource::collection($result['data'])
            ->additional(['meta' => $result['meta']]);
    }

    public function update(
        UpdateAuthorRequest $request,
        int $author
    ): AuthorResource {
        $authorData = $this->authorService->update(
            $author,
            $request->validated()
        );

        return new AuthorResource($authorData);
    }

    public function destroy(int $author): JsonResponse
    {
        $this->authorService->delete($author);

        return response()->json([
            'message' => 'Author deleted successfully.',
        ]);
    }
}
