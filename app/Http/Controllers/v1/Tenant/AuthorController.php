<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Author\StoreAuthorRequest;
use App\Http\Requests\Author\UpdateAuthorRequest;
use App\Http\Resources\AuthorResource;
use App\Services\AuthorService;
use Illuminate\Http\JsonResponse;

class AuthorController extends Controller
{
    public function __construct(
        protected AuthorService $authorService
    ) {}

    public function index()
    {
        return AuthorResource::collection(
            $this->authorService->getAll()
        );
    }

    public function store(StoreAuthorRequest $request): AuthorResource
    {
        $author = $this->authorService->create(
            $request->validated()
        );

        return new AuthorResource($author);
    }

    public function show(int $author): AuthorResource
    {
        $authorData = $this->authorService->getById($author);

        abort_if(!$authorData, 404, 'Author not found.');

        return new AuthorResource($authorData);
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
